<?php

declare(strict_types=1);

namespace Framework\Routing;

class Route
{
    private string $method;
    private string $path;
    private string $name;
    private mixed $handler;
    private array $middleware;
    private ?string $group;
    private array $wheres = [];
    private array $defaults = [];
    private ?string $compiledRegex = null;
    private array $paramNames = [];

    public function __construct(
        string $method,
        string $path,
        mixed $handler,
        string $name = '',
        array $middleware = [],
        ?string $group = null,
    ) {
        $this->method = strtoupper($method);
        $this->path = '/' . trim($path, '/') ?: '/';
        $this->handler = $handler;
        $this->name = $name ?: ($this->method . ':' . $this->path);
        $this->middleware = $middleware;
        $this->group = $group;
        $this->compile();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['method'],
            $data['path'],
            $data['handler'],
            $data['name'] ?? '',
            $data['middleware'] ?? [],
            $data['group'] ?? null,
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHandler(): mixed
    {
        return $this->handler;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getWheres(): array
    {
        return $this->wheres;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function where(string|array $name, ?string $pattern = null): self
    {
        if (is_array($name)) {
            foreach ($name as $key => $value) {
                $this->wheres[$key] = $value;
            }
        } else {
            $this->wheres[$name] = $pattern ?? '[^/]+';
        }
        $this->compile();
        return $this;
    }

    public function defaults(array $defaults): self
    {
        $this->defaults = array_merge($this->defaults, $defaults);
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array)$middleware);
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function match(string $path): array|false
    {
        if ($this->compiledRegex === null) {
            return false;
        }

        if (!preg_match($this->compiledRegex, $path, $matches)) {
            return false;
        }

        $params = [];
        foreach ($this->paramNames as $name) {
            if (isset($matches[$name]) && $matches[$name] !== '') {
                $params[$name] = $matches[$name];
            } elseif (isset($this->defaults[$name])) {
                $params[$name] = $this->defaults[$name];
            }
        }

        return $params;
    }

    public function generateUrl(array $parameters = []): string
    {
        $path = $this->path;

        foreach ($parameters as $key => $value) {
            $path = str_replace('{' . $key . '}', (string)$value, $path);
            $path = str_replace('{' . $key . ':...}', (string)$value, $path);
        }

        $path = preg_replace('/\{[^}]+\}/', '', $path);

        $extra = array_diff_key($parameters, array_flip($this->paramNames));
        if (!empty($extra)) {
            $path .= '?' . http_build_query($extra);
        }

        return '/' . ltrim($path, '/');
    }

    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'path' => $this->path,
            'name' => $this->name,
            'handler' => $this->handler,
            'middleware' => $this->middleware,
            'group' => $this->group,
        ];
    }

    private function compile(): void
    {
        $this->paramNames = [];
        $regex = '#^';

        $parts = explode('/', trim($this->path, '/'));
        if (empty($parts) || $parts === ['']) {
            $regex .= '/$#';
            $this->compiledRegex = $regex;
            return;
        }

        $regex .= '/';
        $partCount = count($parts);

        for ($i = 0; $i < $partCount; $i++) {
            $part = $parts[$i];

            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $inner = trim($part, '{}');

                if (str_contains($inner, '...')) {
                    $paramName = trim($inner, '.');
                    $this->paramNames[] = $paramName;
                    $pattern = $this->wheres[$paramName] ?? '.+';
                    $regex .= '(?P<' . $paramName . '>' . $pattern . ')';
                    break;
                }

                $paramName = $inner;
                $pattern = '[^/]+';

                if (str_contains($inner, ':')) {
                    [$paramName, $pattern] = explode(':', $inner, 2);
                }

                if (isset($this->wheres[$paramName])) {
                    $pattern = $this->wheres[$paramName];
                }

                $this->paramNames[] = $paramName;

                $isLast = $i === $partCount - 1;
                $optional = isset($this->defaults[$paramName]);

                if ($optional) {
                    $regex .= '(?:' . '(?P<' . $paramName . '>' . $pattern . ')' . '/?)?';
                } else {
                    $regex .= '(?P<' . $paramName . '>' . $pattern . ')' . '/';
                }
            } else {
                $regex .= preg_quote($part, '#') . '/';
            }
        }

        $regex = rtrim($regex, '/') . '$#';

        $this->compiledRegex = $regex;
    }
}
