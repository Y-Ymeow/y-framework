<?php

declare(strict_types=1);

namespace Framework\Validation;

class Validator
{
    private array $data = [];
    private array $rules = [];
    private array $errors = [];
    private array $messages = [];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $value = $this->getData($field);
            $rules = $this->parseRules($ruleString);

            foreach ($rules as $rule => $params) {
                $this->validateRule($field, $value, $rule, $params);
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function failed(): bool
    {
        return !$this->validate();
    }

    public function errorsFor(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    private function validateRule(string $field, mixed $value, string $rule, array $params): void
    {
        $passed = match ($rule) {
            'required' => $this->validateRequired($value),
            'nullable' => true,
            'email' => $this->validateEmail($value),
            'string' => is_string($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'numeric' => is_numeric($value),
            'array' => is_array($value),
            'boolean' => in_array($value, [true, false, 0, 1, '0', '1'], true),
            'min' => $this->validateMin($value, $params),
            'max' => $this->validateMax($value, $params),
            'between' => $this->validateBetween($value, $params),
            'size' => $this->validateSize($value, $params),
            'in' => in_array($value, $params),
            'not_in' => !in_array($value, $params),
            'unique' => $this->validateUnique($value, $params),
            'exists' => $this->validateExists($value, $params),
            'confirmed' => $value === $this->getData($field . '_confirmation'),
            'same' => $value === $this->getData($params[0]),
            'different' => $value !== $this->getData($params[0]),
            'regex' => preg_match($params[0], (string)$value),
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'ip' => filter_var($value, FILTER_VALIDATE_IP) !== false,
            'alpha' => ctype_alpha((string)$value),
            'alpha_num' => ctype_alnum((string)$value),
            'alpha_dash' => preg_match('/^[a-zA-Z0-9_-]+$/', (string)$value),
            'starts_with' => str_starts_with((string)$value, $params[0]),
            'ends_with' => str_ends_with((string)$value, $params[0]),
            'date' => strtotime((string)$value) !== false,
            'date_format' => \DateTime::createFromFormat($params[0], (string)$value) !== false,
            'before' => strtotime((string)$value) < strtotime($params[0]),
            'after' => strtotime((string)$value) > strtotime($params[0]),
            'before_or_equal' => strtotime((string)$value) <= strtotime($params[0]),
            'after_or_equal' => strtotime((string)$value) >= strtotime($params[0]),
            'timezone' => in_array((string)$value, timezone_identifiers_list()),
            'file' => is_array($value) && isset($value['tmp_name']),
            'mimes' => $this->validateMimes($value, $params),
            'mimetypes' => $this->validateMimetypes($value, $params),
            'dimensions' => $this->validateDimensions($value, $params),
            'image' => $this->validateImage($value),
            'distinct' => $this->validateDistinct($value, $params),
            'digits' => preg_match('/^\d{' . $params[0] . '}$/', (string)$value),
            'digits_between' => preg_match('/^\d{' . $params[0] . ',' . $params[1] . '}$/', (string)$value),
            default => throw new \InvalidArgumentException("Rule '{$rule}' is not defined."),
        };

        if (!$passed) {
            $this->errors[$field][] = $this->getMessage($field, $rule, $params);
        }
    }

    private function validateRequired(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        if (is_array($value) && empty($value)) {
            return false;
        }
        return true;
    }

    private function validateEmail(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateMin(mixed $value, array $params): bool
    {
        $min = $params[0];
        if (is_numeric($value)) {
            return $value >= $min;
        }
        return strlen((string)$value) >= $min;
    }

    private function validateMax(mixed $value, array $params): bool
    {
        $max = $params[0];
        if (is_numeric($value)) {
            return $value <= $max;
        }
        return strlen((string)$value) <= $max;
    }

    private function validateBetween(mixed $value, array $params): bool
    {
        $min = $params[0];
        $max = $params[1];
        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }
        return strlen((string)$value) >= $min && strlen((string)$value) <= $max;
    }

    private function validateSize(mixed $value, array $params): bool
    {
        $size = $params[0];
        if (is_numeric($value)) {
            return $value == $size;
        }
        return strlen((string)$value) == $size;
    }

    private function validateImage(mixed $value): bool
    {
        if (!is_array($value) || !isset($value['tmp_name'])) {
            return false;
        }
        $imageInfo = getimagesize($value['tmp_name']);
        return $imageInfo !== false;
    }

    /**
     * 验证唯一性
     * 格式: unique:table,column,exceptId,idColumn
     */
    private function validateUnique(mixed $value, array $params): bool
    {
        $table = $params[0] ?? '';
        $column = $params[1] ?? 'id';

        if (empty($table)) {
            return true;
        }

        try {
            $query = db()->table($table)->where($column, $value);

            // 排除指定 ID
            if (isset($params[2])) {
                $exceptId = $params[2];
                $idColumn = $params[3] ?? 'id';
                $query->where($idColumn, '!=', $exceptId);
            }

            return $query->count() === 0;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * 验证存在性
     * 格式: exists:table,column
     */
    private function validateExists(mixed $value, array $params): bool
    {
        $table = $params[0] ?? '';
        $column = $params[1] ?? 'id';

        if (empty($table)) {
            return true;
        }

        try {
            return db()->table($table)->where($column, $value)->count() > 0;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * 验证文件 MIME 类型
     */
    private function validateMimes(mixed $value, array $params): bool
    {
        if (!is_array($value) || !isset($value['name'])) {
            return false;
        }

        $ext = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));
        return in_array($ext, $params);
    }

    /**
     * 验证文件 MIME 类型（通过文件内容）
     */
    private function validateMimetypes(mixed $value, array $params): bool
    {
        if (!is_array($value) || !isset($value['tmp_name'])) {
            return false;
        }

        $mimeType = mime_content_type($value['tmp_name']);
        return $mimeType !== false && in_array($mimeType, $params);
    }

    /**
     * 验证图片尺寸
     */
    private function validateDimensions(mixed $value, array $params): bool
    {
        if (!is_array($value) || !isset($value['tmp_name'])) {
            return false;
        }

        $imageInfo = getimagesize($value['tmp_name']);
        if ($imageInfo === false) {
            return false;
        }

        [$width, $height] = $imageInfo;

        foreach ($params as $param) {
            if (preg_match('/^min_width=(\d+)$/', $param, $m) && $width < (int)$m[1]) {
                return false;
            }
            if (preg_match('/^max_width=(\d+)$/', $param, $m) && $width > (int)$m[1]) {
                return false;
            }
            if (preg_match('/^min_height=(\d+)$/', $param, $m) && $height < (int)$m[1]) {
                return false;
            }
            if (preg_match('/^max_height=(\d+)$/', $param, $m) && $height > (int)$m[1]) {
                return false;
            }
        }

        return true;
    }

    /**
     * 验证数组字段值不重复
     */
    private function validateDistinct(mixed $value, array $params): bool
    {
        if (!is_array($value)) {
            return false;
        }

        $field = $params[0] ?? null;
        $values = [];

        foreach ($value as $item) {
            $checkValue = $field ? ($item[$field] ?? null) : $item;
            if (in_array($checkValue, $values, true)) {
                return false;
            }
            $values[] = $checkValue;
        }

        return true;
    }

    private function getData(string $field): mixed
    {
        $keys = explode('.', $field);
        $data = $this->data;

        foreach ($keys as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } else {
                return null;
            }
        }

        return $data;
    }

    private function parseRules(string $ruleString): array
    {
        $rules = [];
        $parts = explode('|', $ruleString);

        foreach ($parts as $part) {
            if (str_contains($part, ':')) {
                [$rule, $params] = explode(':', $part, 2);
                $rules[$rule] = explode(',', $params);
            } else {
                $rules[$part] = [];
            }
        }

        return $rules;
    }

    private function getMessage(string $field, string $rule, array $params): string
    {
        $key = "{$field}.{$rule}";

        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        }

        return match ($rule) {
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'min' => "The {$field} must be at least {$params[0]}.",
            'max' => "The {$field} must not exceed {$params[0]}.",
            'between' => "The {$field} must be between {$params[0]} and {$params[1]}.",
            'size' => "The {$field} must be exactly {$params[0]}.",
            'in' => "The {$field} must be one of: " . implode(', ', $params),
            'not_in' => "The {$field} must not be one of: " . implode(', ', $params),
            'unique' => "The {$field} has already been taken.",
            'exists' => "The selected {$field} is invalid.",
            'regex' => "The {$field} format is invalid.",
            'url' => "The {$field} must be a valid URL.",
            'ip' => "The {$field} must be a valid IP address.",
            'confirmed' => "The {$field} confirmation does not match.",
            'same' => "The {$field} must match {$params[0]}.",
            'different' => "The {$field} must be different from {$params[0]}.",
            'integer' => "The {$field} must be an integer.",
            'numeric' => "The {$field} must be numeric.",
            'alpha' => "The {$field} must only contain letters.",
            'alpha_num' => "The {$field} must only contain letters and numbers.",
            'alpha_dash' => "The {$field} must only contain letters, numbers, dashes, and underscores.",
            'date' => "The {$field} must be a valid date.",
            'date_format' => "The {$field} must match the format {$params[0]}.",
            'before' => "The {$field} must be before {$params[0]}.",
            'after' => "The {$field} must be after {$params[0]}.",
            'file' => "The {$field} must be a file.",
            'image' => "The {$field} must be an image.",
            'mimes' => "The {$field} must be a file of type: " . implode(', ', $params),
            'dimensions' => "The {$field} has invalid image dimensions.",
            'distinct' => "The {$field} field has a duplicate value.",
            'digits' => "The {$field} must be {$params[0]} digits.",
            'digits_between' => "The {$field} must be between {$params[0]} and {$params[1]} digits.",
            'timezone' => "The {$field} must be a valid timezone.",
            default => "The {$field} field is invalid.",
        };
    }
}
