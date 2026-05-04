<?php

declare(strict_types=1);

namespace Framework\Module\Mail;

class PendingMail
{
    private MailManager $manager;
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];

    public function __construct(MailManager $manager)
    {
        $this->manager = $manager;
    }

    public function to(string|array $to): self
    {
        $this->to = is_array($to) ? $to : [$to];
        return $this;
    }

    public function cc(string|array $cc): self
    {
        $this->cc = is_array($cc) ? $cc : [$cc];
        return $this;
    }

    public function bcc(string|array $bcc): self
    {
        $this->bcc = is_array($bcc) ? $bcc : [$bcc];
        return $this;
    }

    public function send(Mailable $mailable): bool
    {
        $mailable->to($this->to);
        if (!empty($this->cc)) $mailable->cc($this->cc);
        if (!empty($this->bcc)) $mailable->bcc($this->bcc);

        return $this->manager->send($mailable);
    }
}
