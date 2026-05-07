<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components;

use Framework\UX\Form\FormBuilder;
use Framework\View\Base\Element;

abstract class ComponentType
{
    abstract public function name(): string;
    abstract public function label(): string;
    abstract public function icon(): string;
    abstract public function category(): string;

    public function settings(FormBuilder $form): void
    {
    }

    public function defaultSettings(): array
    {
        $form = new FormBuilder();
        $this->settings($form);
        return $form->getDefaults();
    }

    abstract public function render(array $settings): Element;

    protected function setting(array $settings, string $key, mixed $fallback = ''): mixed
    {
        return $settings[$key] ?? $fallback;
    }
}
