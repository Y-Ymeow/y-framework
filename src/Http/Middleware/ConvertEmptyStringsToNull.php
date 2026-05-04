<?php

declare(strict_types=1);

namespace Framework\Http\Middleware;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;

class ConvertEmptyStringsToNull implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, mixed ...$params): Response
    {
        $all = $request->all();
        $converted = $this->convert($all);
        $request->merge($converted);

        return $next($request);
    }

    private function convert(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            } elseif (is_array($value)) {
                $data[$key] = $this->convert($value);
            }
        }

        return $data;
    }
}
