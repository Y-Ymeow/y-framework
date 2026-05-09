<?php

declare(strict_types=1);

namespace Framework\UX\Form\Contracts;

interface FormField extends FormComponent
{
    public function getType(): string;

    public function getPlaceholder(): ?string;

    public function placeholder(string $placeholder): static;

    public function getHelp(): string|array|null;

    public function help(string|array $help): static;

    public function getValidationRules(): array;

    public function rules(array $rules): static;
}
