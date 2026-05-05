<?php

declare(strict_types=1);

namespace Framework\Http\Client;

use Framework\Exception\HttpClientException;

class HttpClient
{
    private string $baseUrl = '';
    private array $defaultHeaders = [];
    private int $timeout = 30;
    private bool $sslVerification = true;

    public static function make(string $baseUrl = ''): self
    {
        $client = new self();
        $client->baseUrl = rtrim($baseUrl, '/');
        return $client;
    }

    public function withHeaders(array $headers): self
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    public function withToken(string $token, string $type = 'Bearer'): self
    {
        $this->defaultHeaders['Authorization'] = "{$type} {$token}";
        return $this;
    }

    public function withBasicAuth(string $username, string $password): self
    {
        $this->defaultHeaders['Authorization'] = 'Basic ' . base64_encode("{$username}:{$password}");
        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function withoutSslVerification(): self
    {
        $this->sslVerification = false;
        return $this;
    }

    public function get(string $url, array $query = []): HttpClientResponse
    {
        return $this->request('GET', $url, $query);
    }

    public function post(string $url, mixed $data = null): HttpClientResponse
    {
        return $this->request('POST', $url, [], $data);
    }

    public function put(string $url, mixed $data = null): HttpClientResponse
    {
        return $this->request('PUT', $url, [], $data);
    }

    public function patch(string $url, mixed $data = null): HttpClientResponse
    {
        return $this->request('PATCH', $url, [], $data);
    }

    public function delete(string $url, mixed $data = null): HttpClientResponse
    {
        return $this->request('DELETE', $url, [], $data);
    }

    public function async(): AsyncHttpClient
    {
        return new AsyncHttpClient($this->defaultHeaders, $this->timeout, $this->sslVerification);
    }

    private function request(string $method, string $url, array $query = [], mixed $data = null): HttpClientResponse
    {
        $fullUrl = $this->buildUrl($url, $query);
        $options = $this->getOptions($method, $data);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->prepareHeaders($options),
            CURLOPT_SSL_VERIFYPEER => $this->sslVerification,
            CURLOPT_SSL_VERIFYHOST => $this->sslVerification ? 2 : 0,
        ]);

        if (isset($options['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body']);
        }

        $startTime = microtime(true);
        $body = curl_exec($ch);
        $elapsed = microtime(true) - $startTime;

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $rawHeaders = substr(curl_exec($ch), 0, $headerSize);

        curl_close($ch);

        return new HttpClientResponse(
            $status,
            $body,
            $this->parseHeaders($rawHeaders),
            $elapsed
        );
    }

    private function buildUrl(string $url, array $query = []): string
    {
        if ($this->baseUrl && !str_starts_with($url, 'http')) {
            $url = $this->baseUrl . '/' . ltrim($url, '/');
        }

        if (!empty($query)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($query);
        }

        return $url;
    }

    private function getOptions(string $method, mixed $data = null): array
    {
        $options = [
            'headers' => $this->defaultHeaders,
        ];

        if ($data !== null) {
            if (is_array($data) && !isset($this->defaultHeaders['Content-Type'])) {
                $options['headers']['Content-Type'] = 'application/json';
                $options['body'] = json_encode($data);
            } elseif (is_string($data)) {
                $options['body'] = $data;
            } else {
                $options['headers']['Content-Type'] = 'application/json';
                $options['body'] = json_encode($data);
            }
        }

        return $options;
    }

    private function prepareHeaders(array $options): array
    {
        $headers = [];
        foreach ($options['headers'] as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        return $headers;
    }

    private function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[trim($key)][] = trim($value);
            }
        }
        return $headers;
    }
}
