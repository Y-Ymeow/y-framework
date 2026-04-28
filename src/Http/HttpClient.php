<?php

declare(strict_types=1);

namespace Framework\Http;

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

class AsyncHttpClient
{
    private array $headers;
    private int $timeout;
    private bool $sslVerification;
    private array $requests = [];

    public function __construct(array $headers, int $timeout, bool $sslVerification)
    {
        $this->headers = $headers;
        $this->timeout = $timeout;
        $this->sslVerification = $sslVerification;
    }

    public function get(string $url, array $query = []): self
    {
        $this->requests[] = ['method' => 'GET', 'url' => $url, 'query' => $query];
        return $this;
    }

    public function post(string $url, mixed $data = null): self
    {
        $this->requests[] = ['method' => 'POST', 'url' => $url, 'data' => $data];
        return $this;
    }

    public function put(string $url, mixed $data = null): self
    {
        $this->requests[] = ['method' => 'PUT', 'url' => $url, 'data' => $data];
        return $this;
    }

    public function delete(string $url, mixed $data = null): self
    {
        $this->requests[] = ['method' => 'DELETE', 'url' => $url, 'data' => $data];
        return $this;
    }

    public function send(): array
    {
        $multiHandle = curl_multi_init();
        $curlHandles = [];

        foreach ($this->requests as $index => $req) {
            $url = $req['url'];
            if (!empty($req['query'])) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator . http_build_query($req['query']);
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CUSTOMREQUEST => $req['method'],
                CURLOPT_SSL_VERIFYPEER => $this->sslVerification,
                CURLOPT_SSL_VERIFYHOST => $this->sslVerification ? 2 : 0,
            ]);

            if (isset($req['data']) && $req['data'] !== null) {
                if (is_array($req['data'])) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($req['data']));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $req['data']);
                }
            }

            $headers = [];
            foreach ($this->headers as $key => $value) {
                $headers[] = $key . ': ' . $value;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[$index] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        $results = [];
        foreach ($curlHandles as $index => $ch) {
            $body = curl_multi_getcontent($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $results[] = new HttpClientResponse(
                $status,
                $body,
                [],
                0.0
            );

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);

        return $results;
    }
}

class HttpClientResponse
{
    public function __construct(
        private int $status,
        private string $body,
        private array $headers,
        private float $elapsed,
    ) {}

    public function status(): int { return $this->status; }
    public function body(): string { return $this->body; }
    public function headers(): array { return $this->headers; }
    public function elapsed(): float { return $this->elapsed; }

    public function json(): ?array
    {
        $data = json_decode($this->body, true);
        return is_array($data) ? $data : null;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function failed(): bool
    {
        return !$this->successful();
    }

    public function clientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    public function serverError(): bool
    {
        return $this->status >= 500;
    }

    public function header(string $key): ?string
    {
        foreach ($this->headers[$key] ?? [] as $value) {
            return $value;
        }
        return null;
    }

    public function throw(): self
    {
        if ($this->failed()) {
            throw new \RuntimeException("HTTP request failed with status {$this->status}: {$this->body}");
        }
        return $this;
    }
}
