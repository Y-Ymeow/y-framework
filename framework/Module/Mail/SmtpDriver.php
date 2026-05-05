<?php

declare(strict_types=1);

namespace Framework\Module\Mail;

class SmtpDriver implements MailDriverInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function send(array $to, string $subject, string $body, array $from = [], array $headers = []): bool
    {
        $fromAddress = $from['address'] ?? 'noreply@example.com';
        $fromName = $from['name'] ?? '';

        $headers = array_merge([
            'From' => $fromName ? "{$fromName} <{$fromAddress}>" : $fromAddress,
            'Reply-To' => $fromAddress,
            'X-Mailer' => 'PHP/' . PHP_VERSION,
            'Content-Type' => 'text/html; charset=UTF-8',
        ], $headers);

        if (!empty($to)) {
            $headers['To'] = implode(', ', $to);
        }

        $headerStr = '';
        foreach ($headers as $key => $value) {
            $headerStr .= "{$key}: {$value}\r\n";
        }

        return mail(implode(', ', $to), $subject, $body, $headerStr);
    }
}
