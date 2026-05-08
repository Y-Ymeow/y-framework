<?php

declare(strict_types=1);

namespace App\Actions;

use Framework\UX\Form\Components\LiveTextInput;

class UnsafeAction
{
    // 故意不加 #[LiveAction] 标记，用于测试安全校验
    public static function doSomething(LiveTextInput $field, array $params = []): void
    {
        $field->inputValue = 'unsafe';
    }
}
