<?php

declare(strict_types=1);

namespace Framework\Module\Mail;

class SendmailDriver implements MailDriverInterface
{
    private string $path;

    public function __construct(array $config = [])
    {
        $this->path = $config['path'] ?? '/usr/sbin/sendmail -bs';
    }

    public function send(array $to, string $subject, string $body, array $from = [], array $headers = []): bool
    {
        $fromAddress = $from['address'] ?? 'noreply@example.com';

        $headers = array_merge([
            'From' => $fromAddress,
            'Subject' => $subject,
            'Content-Type' => 'text/html; charset=UTF-8',
        ], $headers);

        $headerStr = '';
        foreach ($headers as $key => $value) {
            $headerStr .= "{$key}: {$value}\r\n";
        }

        return mail(implode(', ', $to), $subject, $body, $headerStr);
    }
}
