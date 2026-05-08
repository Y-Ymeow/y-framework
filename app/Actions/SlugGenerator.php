<?php

declare(strict_types=1);

namespace App\Actions;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\UX\Form\Components\LiveTextInput;

class SlugGenerator
{
    #[LiveAction]
    public static function generate(LiveTextInput $field, array $params = []): void
    {
        $title = $field->getParent()?->title ?? '';
        $field->inputValue = self::slug($title);

        $field->emit('slugGenerated', ['slug' => $field->inputValue]);
    }

    private static function slug(string $text): string
    {
        $text = preg_replace('/[^a-zA-Z0-9\s-]+/', '', $text);
        $text = preg_replace('/\s+/', '-', trim($text));
        return strtolower($text);
    }
}
