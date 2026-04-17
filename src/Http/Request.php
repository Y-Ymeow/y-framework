<?php

declare(strict_types=1);

namespace Framework\Http;

final class Request
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $query
     * @param array<string, mixed> $request
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $headers = [],
        private readonly array $query = [],
        private readonly array $request = [],
        private array $attributes = [],
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        return new self($method, $uri, $headers, $_GET, $_POST);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->request;
    }

    public function header(string $key, ?string $default = null): ?string
    {
        return $this->headers[$key] ?? $this->headers[strtolower($key)] ?? $default;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    public function routeParam(string $key, mixed $default = null): mixed
    {
        $routeParameters = $this->attributes['routeParameters'] ?? [];

        return is_array($routeParameters) ? ($routeParameters[$key] ?? $default) : $default;
    }

    /**
     * 极简验证逻辑
     */
    public function validate(array $rules): array
    {
        $data = $this->all();
        $validated = [];
        $errors = [];

        foreach ($rules as $field => $ruleStr) {
            $value = $data[$field] ?? null;
            $rulesArray = explode('|', $ruleStr);

            foreach ($rulesArray as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $errors[$field][] = "Field {$field} is required.";
                }
                // 这里可以继续添加更多原生规则，如 email, min, max
            }

            $validated[$field] = $value;
        }

        if (!empty($errors)) {
            // 这里可以抛出异常，由 Kernel 捕获并返回 422
            throw new \RuntimeException("Validation failed: " . json_encode($errors));
        }

        return $validated;
    }

    /**
     * 极简授权逻辑
     */
    public function can(callable $checker): void
    {
        if (!$checker($this)) {
            throw new \RuntimeException("Unauthorized action.", 403);
        }
    }

    /**
     * 处理文件上传
     */
    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function moveFile(string $key, string $destination): ?string
    {
        $file = $this->file($key);
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $filename = bin2hex(random_bytes(8)) . '_' . basename($file['name']);
        $target = rtrim($destination, '/') . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            return $filename;
        }

        return null;
    }
}
