<?php

declare(strict_types=1);

namespace Admin\PageBuilder\StylePanel;

use Framework\UX\Form\FormBuilder;

interface StyleGroupInterface
{
    public function name(): string;
    public function label(): string;
    public function icon(): string;
    public function canHandle(string $baseClass): bool;
    public function fields(FormBuilder $form, array $currentClasses): void;
    public function extractClasses(array $data): array;
}
