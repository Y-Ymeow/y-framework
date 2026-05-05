<?php

declare(strict_types=1);

namespace Framework\Component\Live;

class LiveResponse
{
    private array $operations = [];
    private array $domPatches = [];
    private array $fragments = [];

    public static function make(): self
    {
        return new self();
    }

    public function update(string $field, mixed $value): self
    {
        $this->operations[] = ['op' => 'update', 'target' => $field, 'value' => $value];
        return $this;
    }

    public function html(string $selector, string $html): self
    {
        $this->operations[] = ['op' => 'html', 'selector' => $selector, 'html' => $html];
        return $this;
    }

    public function domPatch(string $selector, string $html): self
    {
        $this->domPatches[] = ['selector' => $selector, 'html' => $html];
        return $this;
    }

    public function append(string $selector, string $html): self
    {
        $this->operations[] = ['op' => 'append', 'selector' => $selector, 'html' => $html];
        return $this;
    }

    public function appendHtml(string $selector, string $html): self
    {
        $this->operations[] = ['op' => 'append', 'selector' => $selector, 'html' => $html];
        return $this;
    }

    public function remove(string $selector): self
    {
        $this->operations[] = ['op' => 'remove', 'selector' => $selector];
        return $this;
    }

    public function addClass(string $selector, string $class): self
    {
        $this->operations[] = ['op' => 'addClass', 'selector' => $selector, 'class' => $class];
        return $this;
    }

    public function removeClass(string $selector, string $class): self
    {
        $this->operations[] = ['op' => 'removeClass', 'selector' => $selector, 'class' => $class];
        return $this;
    }

    public function toast(string $message, string $type = 'success', int $duration = 3000, ?string $title = null): self
    {
        $this->operations[] = ['op' => 'ux:toast', 'message' => $message, 'type' => $type, 'duration' => $duration, 'title' => $title];
        return $this;
    }

    public function notify(string $title, string $message, string $type = 'info', int $duration = 5000): self
    {
        $this->operations[] = ['op' => 'ux:toast', 'message' => $message, 'type' => $type, 'duration' => $duration, 'title' => $title];
        return $this;
    }

    public function openModal(string $id): self
    {
        $this->operations[] = ['op' => 'openModal', 'id' => $id];
        return $this;
    }

    public function closeModal(string $id): self
    {
        $this->operations[] = ['op' => 'closeModal', 'id' => $id];
        return $this;
    }

    public function redirect(string $url): self
    {
        $this->operations[] = ['op' => 'redirect', 'url' => $url];
        return $this;
    }

    public function reload(): self
    {
        $this->operations[] = ['op' => 'reload'];
        return $this;
    }

    public function js(string $code): self
    {
        throw new \LogicException('LiveResponse::js() is disabled. Use dispatch() and handle the event on the client instead.');
    }

    public function dispatch(string $event, ?string $target = null, mixed $detail = null): self
    {
        $this->operations[] = ['op' => 'dispatch', 'event' => $event, 'target' => $target, 'detail' => $detail];
        return $this;
    }

    public function fragment(string $name, string $html, string $mode = 'replace'): self
    {
        $this->fragments[] = [
            'name' => $name,
            'html' => $html,
            'mode' => $mode,
        ];
        return $this;
    }

    public function fragments(array $fragments): self
    {
        foreach ($fragments as $name => $html) {
            $this->fragment((string) $name, (string) $html);
        }
        return $this;
    }

    public function toArray(): array
    {
        return [
            'operations' => $this->operations,
            'domPatches' => $this->domPatches,
            'fragments' => $this->fragments,
        ];
    }
}
