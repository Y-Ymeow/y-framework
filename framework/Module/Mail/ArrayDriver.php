<?php

declare(strict_types=1);

namespace Framework\Module\Mail;

class ArrayDriver implements MailDriverInterface
{
    public static array $sent = [];

    public function send(array $to, string $subject, string $body, array $from = [], array $headers = []): bool
    {
        self::$sent[] = compact('to', 'subject', 'body', 'from', 'headers');
        return true;
    }

    public static function getSent(): array
    {
        return self::$sent;
    }

    public static function reset(): void
    {
        self::$sent = [];
    }
}
