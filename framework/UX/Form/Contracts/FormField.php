<?php

declare(strict_types=1);

namespace Framework\UX\Form\Contracts;

interface FormField extends FormComponent
{
    public function getType(): string;

    public function getPlaceholder(): ?string;

    public function placeholder(string $placeholder): static;

    public function getHelp(): ?string;

    public function help(string $help): static;

    public function getValidationRules(): array;

    public function rules(array $rules): static;
}
