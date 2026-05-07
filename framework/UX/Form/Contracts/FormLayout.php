<?php

declare(strict_types=1);

namespace Framework\UX\Form\Contracts;

interface FormLayout extends FormComponent
{
    public function schema(array|callable $components): static;

    public function getComponents(): array;

    public function hasComponents(): bool;
}
