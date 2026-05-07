<?php

declare(strict_types=1);

namespace Framework\UX\Form\Contracts;

use Framework\View\Base\Element;

interface FormComponent
{
    public function getName(): string;

    public function setName(string $name): static;

    public function getLabel(): string|array|null;

    public function setLabel(string|array $label): static;

    public function isRequired(): bool;

    public function required(bool $required = true): static;

    public function isDisabled(): bool;

    public function disabled(bool $disabled = true): static;

    public function getValue(): mixed;

    public function setValue(mixed $value): static;

    public function getDefault(): mixed;

    public function default(mixed $default): static;

    public function render(): Element;
}
