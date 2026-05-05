<?php

declare(strict_types=1);

namespace Framework\Module\Mail;

interface MailDriverInterface
{
    public function send(array $to, string $subject, string $body, array $from = [], array $headers = []): bool;
}
