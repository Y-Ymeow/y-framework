<?php

declare(strict_types=1);

namespace Framework\Module\Mail;

class LogDriver implements MailDriverInterface
{
    public function send(array $to, string $subject, string $body, array $from = [], array $headers = []): bool
    {
        $logEntry = sprintf(
            "[%s] To: %s | Subject: %s | From: %s",
            date('Y-m-d H:i:s'),
            implode(', ', $to),
            $subject,
            $from['address'] ?? 'noreply@example.com'
        );

        if (function_exists('\\logger')) {
            \logger()->info("Mail sent: {$logEntry}");
        }

        return true;
    }
}
