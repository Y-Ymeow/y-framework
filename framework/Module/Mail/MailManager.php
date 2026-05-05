<?php

declare(strict_types=1);

namespace Framework\Module\Mail;

class MailManager
{
    private string $defaultDriver;
    private array $drivers = [];
    private array $from = ['address' => '', 'name' => ''];

    public function __construct(array $config = [])
    {
        $this->defaultDriver = $config['default'] ?? 'smtp';
        $this->from = $config['from'] ?? ['address' => 'noreply@example.com', 'name' => 'App'];
    }

    public function driver(?string $name = null): MailDriverInterface
    {
        $name ??= $this->defaultDriver;

        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        return $this->drivers[$name] = $this->resolveDriver($name);
    }

    public function extend(string $name, callable $factory): self
    {
        $this->drivers[$name] = $factory();
        return $this;
    }

    public function from(string $address, string $name = ''): self
    {
        $this->from = ['address' => $address, 'name' => $name];
        return $this;
    }

    public function getFrom(): array
    {
        return $this->from;
    }

    public function to(string|array $to): PendingMail
    {
        return (new PendingMail($this))->to($to);
    }

    public function send(Mailable $mailable): bool
    {
        return $this->driver()->send(
            $mailable->getTo(),
            $mailable->getSubject(),
            $mailable->getBody(),
            $mailable->getFrom() ?: $this->from,
            $mailable->getHeaders()
        );
    }

    private function resolveDriver(string $name): MailDriverInterface
    {
        return match ($name) {
            'smtp' => new SmtpDriver(config('mail.mailers.smtp', [])),
            'sendmail' => new SendmailDriver(config('mail.mailers.sendmail', [])),
            'log' => new LogDriver(),
            'array' => new ArrayDriver(),
            default => throw new \InvalidArgumentException("Mail driver [{$name}] is not supported."),
        };
    }
}
