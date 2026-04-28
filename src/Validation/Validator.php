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
            'unique' => true, // Needs implementation
            'exists' => true, // Needs implementation
            'confirmed' => $value === $this->getData($field . '_confirmation'),
            'same' => $value === $this->getData($params[0]),
            'different' => $value !== $this->getData($params[0]),
            'regex' => preg_match($params[0], (string)$value),
            'url' => filter_var($value, FILTER_VALIDATE_URL),
            'ip' => filter_var($value, FILTER_VALIDATE_IP),
            'alpha' => ctype_alpha((string)$value),
            'alpha_num' => ctype_alnum((string)$value),
            'alpha_dash' => preg_match('/^[a-zA-Z0-9_-]+$/', (string)$value),
            'starts_with' => str_starts_with((string)$value, $params[0]),
            'ends_with' => str_ends_with((string)$value, $params[0]),
            'date' => strtotime((string)$value) !== false,
            'before' => strtotime((string)$value) < strtotime($params[0]),
            'after' => strtotime((string)$value) > strtotime($params[0]),
            'file' => is_array($value) && isset($value['tmp_name']),
            'image' => $this->validateImage($value),
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
        // Implementation for file upload validation
        return $value !== null;
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
            'in' => "The {$field} must be one of: " . implode(', ', $params),
            'regex' => "The {$field} format is invalid.",
            'url' => "The {$field} must be a valid URL.",
            'confirmed' => "The {$field} confirmation does not match.",
            'same' => "The {$field} must match {$params[0]}.",
            'different' => "The {$field} must be different from {$params[0]}.",
            'integer' => "The {$field} must be an integer.",
            'numeric' => "The {$field} must be numeric.",
            default => "The {$field} field is invalid.",
        };
    }
}
