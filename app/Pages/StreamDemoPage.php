<?php

declare(strict_types=1);

namespace App\Pages;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\LivePoll;
use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Stream\StreamBuilder;
use Framework\Http\StreamResponse;
use Framework\Component\Live\Sse\SseHub;
use Framework\Routing\Attribute\Route;
use Framework\UX\Feedback\Progress;
use Framework\UX\Display\Card;
use Framework\UX\UI\Button;
use Framework\UX\Form\Input;
use Framework\UX\Layout\Row;
use Framework\View\Base\Element;
use Framework\View\Document\Document;
use Override;

/**
 * Stream / SSE / Poll 功能演示页面
 */
#[Route('/stream-demo')]
class StreamDemoPage extends LiveComponent
{
    public string $chatInput = '';
    public string $chatOutput = '';
    public int $progress = 0;
    public string $status = 'idle';
    public int $pollCount = 0;
    public string $lastMessage = '等待中...';

    public function mount(): void
    {
        Document::sseConfig(['notifications', 'demo']);
    }

    #[Override]
    public function render(): Element
    {
        return
            Element::make('div')
            ->class('p-8 max-w-4xl mx-auto')
            ->children(
                Element::make('h1')
                    ->text('实时功能演示')
                    ->class('text-2xl font-bold mb-8'),

                // ========== 1. Stream 流式响应 ==========
                Card::make()
                    ->children(
                        Element::make('h2')->text('1. Stream 流式响应（AI 对话风格）')->class('text-lg font-bold mb-4'),

                        Input::make()
                            ->placeholder('输入消息测试流式输出...')
                            ->model('chatInput')
                            ->class('mb-4'),

                        Row::make()->children(
                            Button::make()->label('发送（流式）')->primary()->liveAction('chatStream')->stream(),
                            Button::make()->label('模拟进度')->liveAction('progressDemo')->stream()->class('ml-2'),
                            Button::make()->label('清空')->variant('secondary')->liveAction('clearChat')->class('ml-2'),
                        ),

                        Element::make('div')
                            ->class('mt-4 p-4 bg-gray-100 rounded min-h-[100px]')
                            ->attr('data-stream-target', '')
                            ->attr('data-text', 'chatOutput')
                    )
                    ->class('mb-6'),

                // ========== 2. SSE 服务器推送 ==========
                Card::make()
                    ->children(
                        Element::make('h2')->text('2. SSE 服务器推送（实时通知）')->class('text-lg font-bold mb-4'),

                        Row::make()->children(
                            Button::make()->label('推送通知')->primary()->liveAction('pushNotification'),
                        ),

                        Element::make('div')
                            ->class('mt-4 p-4 bg-blue-50 rounded')
                            ->attr('data-text', 'lastMessage')
                    )
                    ->class('mb-6'),

                // ========== 3. Poll 轮询 ==========
                Card::make()
                    ->children(
                        Element::make('h2')->text('3. Poll 轮询（定时检查）')->class('text-lg font-bold mb-4'),

                        Element::make('p')
                            ->attr('data-text', 'pollCount')
                            ->class('mb-2'),

                        Element::make('p')
                            ->attr('data-text', 'status')
                            ->class('mb-4'),

                        Element::make('div')
                            ->class('ux-progress ux-progress-md')
                            ->attr('role', 'progressbar')
                            ->attr('data-bind:aria-valuenow', 'progress')
                            ->child(
                                Element::make('div')
                                    ->class('ux-progress-bar ux-progress-bar-primary')
                                    ->attr('data-bind:style', '"width:" + progress + "%"')
                            ),

                        Button::make()
                            ->label('开始轮询任务')
                            ->primary()
                            ->liveAction('startPollTask')
                            ->class('mt-4')
                    )
                    ->class('mb-6'),

                // ========== 使用说明 ==========
                Card::make()
                    ->children(
                        Element::make('h2')->text('使用说明')->class('text-lg font-bold mb-4'),

                        Element::make('div')->children(
                            Element::make('p')->text('Stream:')->class('font-bold'),
                            Element::make('p')->text('LiveAction 返回 StreamResponse，前端通过 data-stream-target 逐字显示。')->class('text-sm mb-4'),

                            Element::make('p')->text('SSE:')->class('font-bold'),
                            Element::make('p')->text('SseHub::push() 推送，前端 EventSource 接收，触发 LiveAction 更新。')->class('text-sm mb-4'),

                            Element::make('p')->text('Poll:')->class('font-bold'),
                            Element::make('p')->text('#[LivePoll] 注解自动生成 data-poll，前端定时调用 LiveAction，data-text 指令响应式更新。')->class('text-sm'),
                        )
                    )
                    ->class('bg-gray-50')
            );
    }

    // ========== Stream Actions ==========

    #[LiveAction]
    public function chatStream(): StreamResponse
    {
        $message = $this->chatInput ?: 'Hello';
        $this->chatOutput = '';

        return StreamBuilder::create()
            ->thinking('正在思考...')
            ->each($this->simulateAiResponse($message), fn($chunk) => StreamBuilder::textChunk($chunk))
            ->done(['message' => $message])
            ->build();
    }

    #[LiveAction]
    public function progressDemo(): StreamResponse
    {
        return StreamBuilder::create()
            ->text('开始处理...')
            ->each(range(1, 100), fn($i) => StreamBuilder::progressChunk($i, 100))
            ->done(['result' => '处理完成！'])
            ->build();
    }

    #[LiveAction]
    public function clearChat(): void
    {
        $this->chatOutput = '';
        $this->chatInput = '';
    }

    private function simulateAiResponse(string $message): \Generator
    {
        $responses = [
            "收到消息：{$message}",
            "\n\n这是流式响应演示，",
            "每个字符会逐个输出，",
            "模拟 AI 对话的效果。",
            "\n\n✨ StreamBuilder 让流式输出变得简单优雅！",
        ];

        foreach ($responses as $text) {
            foreach (str_split($text) as $char) {
                usleep(30000);
                yield $char;
            }
        }
    }

    // ========== SSE Actions ==========

    #[LiveAction]
    public function pushNotification(): void
    {
        $this->lastMessage = '通知已推送！时间：' . date('H:i:s');

        SseHub::liveAction(
            $this->componentId,
            'onSseNotification',
            ['message' => '这是一条推送通知！时间：' . date('H:i:s')],
            'notifications'
        );
    }

    #[LiveAction]
    public function onSseNotification(): void
    {
        $params = func_get_args();
        $message = $params[0]['message'] ?? '收到通知';
        $this->lastMessage = $message;
    }

    // ========== Poll Actions ==========

    #[LiveAction]
    public function startPollTask(): void
    {
        $this->status = 'running';
        $this->progress = 0;
        $this->pollCount = 0;
    }

    #[LivePoll(interval: 1000, condition: 'status === "running"')]
    public function checkTaskProgress(): void
    {
        $this->pollCount++;
        $this->progress = min(100, $this->progress + 10);

        if ($this->progress >= 100) {
            $this->status = 'completed';
        }
    }
}
