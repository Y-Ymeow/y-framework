<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request;
use Framework\Http\Response;

class TrimStrings
{
    private array $except = [];

    public function __construct(array $except = [])
    {
        $this->except = $except;
    }

    public function handle(Request $request, callable $next): Response
    {
        $all = $request->all();
        $trimmed = $this->trim($all);
        $request->merge($trimmed);

        return $next($request);
    }

    private function trim(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->except, true)) {
                continue;
            }

            if (is_string($value)) {
                $data[$key] = trim($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->trim($value);
            }
        }

        return $data;
    }
}
