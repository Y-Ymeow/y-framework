<?php

declare(strict_types=1);

namespace Framework\Http\Response;

class ApiResponse extends JsonResponse
{
    protected array $body;

    public function __construct(bool $success, mixed $data = null, string $message = '', mixed $errors = null, int $status = 200, array $headers = [])
    {
        $this->body = [
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'errors' => $errors,
        ];
        parent::__construct($this->body, $status, $headers);
    }

    public static function success(mixed $data = null, string $message = 'ok', int $status = 200): static
    {
        return new static(true, $data, $message, null, $status);
    }

    public static function created(mixed $data = null, string $message = 'created'): static
    {
        return new static(true, $data, $message, null, 201);
    }

    public static function noContent(string $message = 'no content'): static
    {
        return new static(true, null, $message, null, 204);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): static
    {
        return new static(false, null, $message, $errors, $status);
    }

    public static function notFound(string $message = 'not found'): static
    {
        return new static(false, null, $message, null, 404);
    }

    public static function validationError(mixed $errors, string $message = 'validation error'): static
    {
        return new static(false, null, $message, $errors, 422);
    }

    public static function unauthorized(string $message = 'unauthorized'): static
    {
        return new static(false, null, $message, null, 401);
    }

    public static function forbidden(string $message = 'forbidden'): static
    {
        return new static(false, null, $message, null, 403);
    }
}