<?php

declare(strict_types=1);

namespace Framework\Http\Client;

use Framework\Exception\HttpClientException;

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
