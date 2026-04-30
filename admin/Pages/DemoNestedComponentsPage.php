<?php

declare(strict_types=1);

namespace Admin\Pages;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\LiveListener;
use Framework\Component\Live\LiveComponent;
use Framework\Http\Response;
use Framework\Routing\Attribute\Get;
use Framework\Routing\Attribute\Route;
use Framework\View\Base\Element;
use Framework\View\Container;
use Framework\View\Document\AssetRegistry;
use Framework\View\Document\Document;
use Framework\View\Text;

#[Route('/admin/demo/nested')]
class DemoNestedComponentsPage
{
    #[Get('/nested-components')]
    public function render()
    {
        Document::setTitle('嵌套组件通信测试');

        Document::uxStatic();
        AssetRegistry::getInstance()->js(vite('resources/js/ux.js'));
        $counter = new CounterComponent()->named('counter-1');
        $stats = new StatsComponent()->named('stats-1');

        return \Framework\Http\Response::html(Container::make()
            ->class('min-h-screen bg-gray-50 p-8')
            ->children(
                Text::h1('嵌套组件通信测试')->class('text-3xl font-bold mb-8 text-gray-800'),

                Container::make()
                    ->class('mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4')
                    ->children(
                        Text::h2('测试说明')->class('text-lg font-semibold mb-2 text-blue-800'),
                        Text::p('1. 点击 Counter 的加减按钮,观察 Stats 是否同步更新')->class('text-sm text-blue-700'),
                        Text::p('2. 点击 Stats 的清除按钮,观察 Counter 是否重置')->class('text-sm text-blue-700'),
                        Text::p('3. 验证:所有组件的状态更新在一个请求内完成')->class('text-sm text-blue-700'),
                    ),

                Container::make()
                    ->class('grid grid-cols-2 gap-6')
                    ->children(
                        Container::make()
                            ->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6')
                            ->children(
                                Text::h2('计数器组件')->class('text-xl font-semibold mb-4 text-gray-700'),
                                $counter,
                            ),

                        Container::make()
                            ->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6')
                            ->children(
                                Text::h2('统计组件')->class('text-xl font-semibold mb-4 text-gray-700'),
                                $stats,
                            ),
                    )
            )->render());
    }
}

class CounterComponent extends LiveComponent
{
    public int $count = 0;
    public int $totalOperations = 0;
    public string $lastAction = '';

    #[LiveAction]
    public function increment(): void
    {
        $this->count++;
        $this->totalOperations++;
        $this->lastAction = 'increment';

        $this->emit('counter:incremented', [
            'count' => $this->count,
            'timestamp' => time(),
        ]);

        $this->refresh('counter-display', 'counter-info');
    }

    #[LiveAction]
    public function decrement(): void
    {
        $this->count--;
        $this->totalOperations++;
        $this->lastAction = 'decrement';

        $this->emit('counter:decremented', [
            'count' => $this->count,
            'timestamp' => time(),
        ]);

        $this->refresh('counter-display', 'counter-info');
    }

    #[LiveAction]
    public function reset(): void
    {
        $this->count = 0;
        $this->totalOperations++;
        $this->lastAction = 'reset';

        $this->emit('counter:reset', [
            'count' => $this->count,
            'timestamp' => time(),
        ]);

        $this->refresh('counter-display', 'counter-info');
    }

    #[LiveListener('stats:cleared')]
    public function onStatsCleared(?array $data = null): void
    {
        $this->count = 0;
        $this->lastAction = 'stats-cleared';
        $this->refresh('counter-display', 'counter-info');
    }

    public function render(): string|Element
    {
        return Container::make()->children(
            Element::make('div')
                ->id('counter-display')
                ->class('text-5xl font-bold text-center mb-6 py-8 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg')
                ->liveFragment('counter-display')
                ->text((string) $this->count),

            Container::make()
                ->class('flex gap-3 mb-4')
                ->children(
                    Element::make('button')
                        ->class('flex-1 px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors font-semibold')
                        ->liveAction('increment')
                        ->text('+1'),

                    Element::make('button')
                        ->class('flex-1 px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors font-semibold')
                        ->liveAction('decrement')
                        ->text('-1'),

                    Element::make('button')
                        ->class('flex-1 px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors font-semibold')
                        ->liveAction('reset')
                        ->text('重置')
                ),

            Element::make('div')
                ->id('counter-info')
                ->class('mt-4 p-4 bg-gray-50 rounded-lg')
                ->liveFragment('counter-info')
                ->children(
                    Text::p('操作次数: ' . $this->totalOperations)->class('text-sm text-gray-600'),
                    Text::p('上次操作: ' . $this->lastAction)->class('text-sm text-gray-600'),
                )
        );
    }
}

class StatsComponent extends LiveComponent
{
    public int $maxValue = 0;
    public int $minValue = 0;
    public int $totalEvents = 0;
    public array $eventLog = [];

    #[LiveListener('counter:incremented')]
    public function onCounterIncremented(?array $data = null): void
    {
        $this->totalEvents++;

        if ($data['count'] > $this->maxValue) {
            $this->maxValue = $data['count'];
        }

        $this->eventLog[] = [
            'type' => 'increment',
            'count' => $data['count'],
            'time' => date('H:i:s', $data['timestamp']),
        ];

        if (count($this->eventLog) > 5) {
            array_shift($this->eventLog);
        }

        $this->refresh('stats-display', 'event-log');
    }

    #[LiveListener('counter:decremented')]
    public function onCounterDecremented(?array $data = null): void
    {
        $this->totalEvents++;

        if ($this->minValue === 0 || $data['count'] < $this->minValue) {
            $this->minValue = $data['count'];
        }

        $this->eventLog[] = [
            'type' => 'decrement',
            'count' => $data['count'],
            'time' => date('H:i:s', $data['timestamp']),
        ];

        if (count($this->eventLog) > 5) {
            array_shift($this->eventLog);
        }

        $this->refresh('stats-display', 'event-log');
    }

    #[LiveListener('counter:reset')]
    public function onCounterReset(?array $data = null): void
    {
        $this->totalEvents++;
        $this->eventLog[] = [
            'type' => 'reset',
            'count' => $data['count'],
            'time' => date('H:i:s', $data['timestamp']),
        ];

        if (count($this->eventLog) > 5) {
            array_shift($this->eventLog);
        }

        $this->refresh('stats-display', 'event-log');
    }

    #[LiveAction]
    public function clearStats(): void
    {
        $this->maxValue = 0;
        $this->minValue = 0;
        $this->totalEvents = 0;
        $this->eventLog = [];

        $this->emit('stats:cleared', [
            'cleared_at' => time(),
        ]);

        $this->refresh('stats-display', 'event-log');
    }

    public function render(): string|Element
    {
        $logItems = array_map(function ($log) {
            $color = match ($log['type']) {
                'increment' => 'text-green-600',
                'decrement' => 'text-red-600',
                'reset' => 'text-gray-600',
                default => 'text-gray-600',
            };

            return Text::p("{$log['time']} - {$log['type']} (值: {$log['count']})")
                ->class("text-xs {$color}");
        }, $this->eventLog);

        return Container::make()->children(
            Element::make('div')
                ->id('stats-display')
                ->class('mb-4 space-y-3')
                ->liveFragment('stats-display')
                ->children(
                    $this->createStatCard('最大值', (string) $this->maxValue, 'bg-green-50 text-green-700 border-green-200'),
                    $this->createStatCard('最小值', (string) $this->minValue, 'bg-red-50 text-red-700 border-red-200'),
                    $this->createStatCard('事件总数', (string) $this->totalEvents, 'bg-blue-50 text-blue-700 border-blue-200'),
                ),

            Element::make('button')
                ->class('w-full px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors mb-4')
                ->liveAction('clearStats')
                ->text('清除所有统计'),

            Element::make('div')
                ->id('event-log')
                ->class('p-4 bg-gray-50 rounded-lg')
                ->liveFragment('event-log')
                ->children(
                    Text::h3('事件日志')->class('text-sm font-semibold mb-2 text-gray-700'),
                    ...$logItems
                )
        );
    }

    private function createStatCard(string $label, string $value, string $classes): Element
    {
        return Container::make()
            ->class("p-4 rounded-lg border {$classes}")
            ->children(
                Text::p($label)->class('text-xs font-medium opacity-75'),
                Text::p($value)->class('text-2xl font-bold mt-1'),
            );
    }
}
