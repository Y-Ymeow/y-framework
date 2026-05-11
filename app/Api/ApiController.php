<?php

declare(strict_types=1);

namespace App\Api;

use Framework\Http\Request\Request;
use Framework\Http\Response\ApiResponse;

abstract class ApiController
{
    protected function getRequest(): Request
    {
        return Request::createFromGlobals();
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->getRequest()->input($key, $default);
    }

    protected function success(mixed $data = null, string $message = 'ok', int $status = 200): ApiResponse
    {
        return ApiResponse::success($data, $message, $status);
    }

    protected function created(mixed $data = null, string $message = 'created'): ApiResponse
    {
        return ApiResponse::created($data, $message);
    }

    protected function noContent(string $message = 'no content'): ApiResponse
    {
        return ApiResponse::noContent($message);
    }

    protected function error(string $message, int $status = 400, mixed $errors = null): ApiResponse
    {
        return ApiResponse::error($message, $status, $errors);
    }

    protected function notFound(string $message = 'not found'): ApiResponse
    {
        return ApiResponse::notFound($message);
    }

    protected function validationError(mixed $errors, string $message = 'validation error'): ApiResponse
    {
        return ApiResponse::validationError($errors, $message);
    }
}