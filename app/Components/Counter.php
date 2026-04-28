<?php

declare(strict_types=1);

namespace App\Components;

use Framework\Component\LiveComponent;
use Framework\Component\Attribute\LiveAction;
use Framework\View\Base\Element;
use function Framework\View\div;
use function Framework\View\button;
use function Framework\View\span;

class Counter extends LiveComponent
{
    public int $count = 0;

    public function mount(): void
    {
        // 组件挂载时的初始化逻辑
    }

    #[LiveAction]
    public function increment(): void
    {
        $this->count++;
    }

    public function render(): string|Element
    {
        return div('p-4 border rounded shadow-sm bg-white')
            ->children(
                span('text-lg font-bold')->text("Count: {$this->count}"),
                button('ml-4 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600')
                    ->attr('data-action', 'increment')
                    ->text('Increment')
            );
    }
}