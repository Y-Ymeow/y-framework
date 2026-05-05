<?php

declare(strict_types=1);

namespace Framework\Module\Mail;

class Mailable
{
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];
    private array $from = [];
    private string $subject = '';
    private string $body = '';
    private string $view = '';
    private array $viewData = [];
    private array $headers = [];

    public function to(array $addresses): self
    {
        $this->to = $addresses;
        return $this;
    }

    public function cc(array $addresses): self
    {
        $this->cc = $addresses;
        return $this;
    }

    public function bcc(array $addresses): self
    {
        $this->bcc = $addresses;
        return $this;
    }

    public function from(string $address, string $name = ''): self
    {
        $this->from = ['address' => $address, 'name' => $name];
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function view(string $view, array $data = []): self
    {
        $this->view = $view;
        $this->viewData = $data;
        return $this;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function getTo(): array { return $this->to; }
    public function getCc(): array { return $this->cc; }
    public function getBcc(): array { return $this->bcc; }
    public function getFrom(): array { return $this->from; }
    public function getSubject(): string { return $this->subject; }
    public function getBody(): string { return $this->body; }
    public function getHeaders(): array { return $this->headers; }

    public function build(): void
    {
    }
}
