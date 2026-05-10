<?php

namespace Admin\Pages;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\LivePoll;
use Framework\Component\Live\Attribute\State;
use Framework\DebugBar\DebugBar;
use Framework\DebugBar\DebugBarStorage;
use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;

class DebugPage extends LiveComponent
{
    #[State]
    public string $activeTab = 'overview';

    #[State]
    public ?array $snapshot = null;

    #[State]
    public int $requestCount = 0;

    #[State]
    public int $sqlCount = 0;

    #[State]
    public string $memory = '';

    #[State]
    public string $duration = '';

    #[State]
    public ?int $selectedRequestIndex = null;

    private bool $mounted = false;

    public function mount(): void
    {
        if ($this->mounted) return;
        $this->mounted = true;
        AssetRegistry::getInstance()->ui();
        AssetRegistry::getInstance()->ux();

        // Start fresh: clear accumulated data from before DebugPage opened
        try {
            $storage = DebugBarStorage::make();
            $storage->save(DebugBar::getInstance()->getKey(), []);
        } catch (\Throwable $e) {}

        $this->loadSnapshot();
    }

    protected function loadSnapshot(): void
    {
        try {
            $storage = DebugBarStorage::make();
            $data = $storage->read(DebugBar::getInstance()->getKey());

            if ($data) {
                $this->snapshot = $data;
                $this->requestCount = $data['panels']['request']['data']['total'] ?? 0;
                $this->sqlCount = $data['panels']['sql']['data']['total_queries'] ?? 0;
                $this->memory = $data['summary']['memory'] ?? '';
                $this->duration = $data['summary']['duration'] ?? '';
            }
        } catch (\Throwable $e) {
            // DebugBar not available
        }
    }

    #[LivePoll(interval: 2000)]
    public function poll(): void
    {
        $this->loadSnapshot();
        $this->refresh('debug-content');
        $this->refresh('debug-header');
    }

    #[LiveAction]
    public function selectTab(array $params): void
    {
        $this->activeTab = $params['tab'] ?? 'overview';
        $this->selectedRequestIndex = null;
        $this->refresh('debug-body');
    }

    #[LiveAction]
    public function toggleRequest(array $params): void
    {
        $index = $params['index'] ?? null;
        $this->selectedRequestIndex = $this->selectedRequestIndex === $index ? null : $index;
        $this->refresh('debug-body');
    }

    #[LiveAction]
    public function clear(): void
    {
        $storage = DebugBarStorage::make();
        $storage->save(DebugBar::getInstance()->getKey(), []);
        $this->snapshot = null;
        $this->requestCount = 0;
        $this->sqlCount = 0;
        $this->memory = '';
        $this->duration = '';
        $this->refresh('debug-body');
    }

    public function render(): Element
    {
        $page = Element::make('div')
            ->class('min-h-screen', 'bg-gray-900', 'text-gray-100', 'font-mono', 'text-sm')
            ->attr('data-poll', '{"poll":{"interval":2000}}');

        $page->child(Element::make('style')->text('.debug-reverse-list{display:flex;flex-direction:column-reverse}'));

        $page->child($this->renderHeader());
        $page->child($this->renderBody());

        return $page;
    }

    protected function renderHeader(): Element
    {
        $header = Element::make('div')
            ->class('bg-gray-800', 'border-b', 'border-gray-700', 'px-6', 'py-3', 'flex', 'items-center', 'justify-between');

        $left = Element::make('div')->class('flex', 'items-center', 'gap-3');
        $left->child(Element::make('span')->class('text-lg', 'font-bold')->text('🐛 Debug Panel'));

        $stats = Element::make('div')->class('flex', 'items-center', 'gap-4', 'text-xs', 'text-gray-400')
            ->liveFragment('debug-header');
        $stats->child(Element::make('span')->text("📡 {$this->requestCount} 请求"));
        $stats->child(Element::make('span')->text("💾 {$this->sqlCount} SQL"));
        $stats->child(Element::make('span')->text("📊 {$this->memory}"));
        $stats->child(Element::make('span')->text("⏱ {$this->duration}"));
        $left->child($stats);

        $right = Element::make('div')->class('flex', 'items-center', 'gap-2');
        $right->child(Element::make('span')->class('text-xs', 'text-green-400')->text('● 自动刷新 2s'));
        $right->child(
            Element::make('button')
                ->class('px-3', 'py-1', 'text-xs', 'bg-gray-700', 'rounded', 'hover:bg-gray-600', 'transition')
                ->liveAction('clear')
                ->text('× 清除')
        );

        $header->child($left);
        $header->child($right);

        return $header;
    }

    protected function renderBody(): Element
    {
        $body = Element::make('div')->class('flex', 'h-[calc(100vh-56px)]')
            ->liveFragment('debug-body');

        $body->child($this->renderSidebar());
        $body->child($this->renderContent());

        return $body;
    }

    protected function renderSidebar(): Element
    {
        $sidebar = Element::make('div')
            ->class('w-48', 'bg-gray-800', 'border-r', 'border-gray-700', 'p-4', 'space-y-1', 'flex-shrink-0', 'overflow-y-auto');

        $tabs = [
            'overview' => ['📊', '概览'],
            'requests' => ['📡', '请求'],
            'sql' => ['💾', 'SQL'],
            'debug' => ['🐛', '调试'],
            'messages' => ['💬', '消息'],
            'routes' => ['🛣', '路由'],
            'session' => ['🔐', '会话'],
            'php' => ['⚙', 'PHP'],
        ];

        foreach ($tabs as $key => [$icon, $label]) {
            $isActive = $key === $this->activeTab;
            $classes = ['w-full', 'text-left', 'px-3', 'py-2', 'rounded', 'text-sm', 'transition', 'flex', 'items-center', 'gap-2'];
            if ($isActive) {
                $classes[] = 'bg-blue-600';
                $classes[] = 'text-white';
            } else {
                $classes[] = 'text-gray-300';
                $classes[] = 'hover:bg-gray-700';
            }
            $btn = Element::make('button')
                ->class(...$classes)
                ->liveAction('selectTab', 'click', ['tab' => $key])
                ->text("{$icon} {$label}");
            $sidebar->child($btn);
        }

        return $sidebar;
    }

    protected function renderContent(): Element
    {
        $content = Element::make('div')
            ->class('flex-1', 'p-6', 'overflow-y-auto', 'bg-gray-900')
            ->liveFragment('debug-content');

        $snapshot = $this->snapshot;

        if (!$snapshot || empty($snapshot)) {
            $content->child(
                Element::make('div')->class('text-center', 'text-gray-500', 'mt-20')
                    ->text('等待数据... 发送一些请求后会自动显示')
            );
            return $content;
        }

        switch ($this->activeTab) {
            case 'overview':
                $this->renderOverviewTab($content, $snapshot);
                break;
            case 'requests':
                $this->renderRequestsTab($content, $snapshot);
                break;
            case 'sql':
                $this->renderSqlTab($content, $snapshot);
                break;
            case 'debug':
                $this->renderDebugTab($content, $snapshot);
                break;
            case 'messages':
                $this->renderMessagesTab($content, $snapshot);
                break;
            case 'routes':
                $this->renderRoutesTab($content, $snapshot);
                break;
            case 'session':
                $this->renderSessionTab($content, $snapshot);
                break;
            case 'php':
                $this->renderPhpTab($content, $snapshot);
                break;
        }

        return $content;
    }

    protected function renderOverviewTab(Element $parent, array $snapshot): void
    {
        $summary = $snapshot['summary'] ?? [];
        $php = $snapshot['php'] ?? [];

        $grid = Element::make('div')->class('grid', 'grid-cols-4', 'gap-4', 'mb-6');

        $cards = [
            ['⏱ 耗时', $summary['duration'] ?? '-', 'bg-blue-900'],
            ['📊 内存', $summary['memory'] ?? '-', 'bg-green-900'],
            ['📡 请求', (string)($this->requestCount), 'bg-purple-900'],
            ['💾 SQL', (string)($this->sqlCount), 'bg-orange-900'],
        ];

        foreach ($cards as [$label, $value, $bg]) {
            $card = Element::make('div')->class($bg, 'rounded-lg', 'p-4');
            $card->child(Element::make('div')->class('text-xs', 'text-gray-400')->text($label));
            $card->child(Element::make('div')->class('text-2xl', 'font-bold', 'mt-1')->text($value));
            $grid->child($card);
        }

        $parent->child($grid);

        $infoTable = Element::make('div')->class('bg-gray-800', 'rounded-lg', 'p-4');
        $infoTable->child(Element::make('h3')->class('text-sm', 'font-semibold', 'mb-3', 'text-gray-300')->text('PHP 环境'));

        $phpInfo = [
            ['PHP 版本', $php['version'] ?? PHP_VERSION],
            ['服务器', $php['server'] ?? $_SERVER['SERVER_SOFTWARE'] ?? '-'],
            ['请求方法', $php['method'] ?? $_SERVER['REQUEST_METHOD'] ?? '-'],
            ['请求 URL', $php['url'] ?? $_SERVER['REQUEST_URI'] ?? '-'],
            ['客户端 IP', $php['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '-'],
            ['时间', $php['time'] ?? date('Y-m-d H:i:s')],
            ['内存峰值', $summary['memory_peak'] ?? '-'],
        ];

        foreach ($phpInfo as [$label, $value]) {
            $row = Element::make('div')->class('flex', 'py-1.5', 'border-b', 'border-gray-700', 'last:border-0');
            $row->child(Element::make('div')->class('w-32', 'text-gray-400')->text($label));
            $row->child(Element::make('div')->class('flex-1', 'text-gray-200')->text($value));
            $infoTable->child($row);
        }

        $parent->child($infoTable);
    }

    protected function renderRequestsTab(Element $parent, array $snapshot): void
    {
        $history = $snapshot['panels']['request']['data']['history'] ?? [];

        if (empty($history)) {
            $parent->child(Element::make('div')->class('text-gray-500')->text('暂无请求记录'));
            return;
        }

        $table = Element::make('div')->class('bg-gray-800', 'rounded-lg', 'overflow-hidden');

        $header = Element::make('div')->class('flex', 'bg-gray-700', 'px-4', 'py-2', 'text-xs', 'text-gray-400', 'font-semibold');
        $header->child(Element::make('div')->class('w-16')->text('类型'));
        $header->child(Element::make('div')->class('w-16')->text('方法'));
        $header->child(Element::make('div')->class('w-20')->text('状态'));
        $header->child(Element::make('div')->class('flex-1')->text('URL'));
        $header->child(Element::make('div')->class('w-20')->text('耗时'));
        $header->child(Element::make('div')->class('w-16')->text('时间'));
        $table->child($header);

        foreach ($history as $idx => $req) {
            $isSelected = $this->selectedRequestIndex === $idx;
            $status = $req['status'] ?? 200;
            $statusColor = $status >= 400 ? 'text-red-400' : ($status >= 300 ? 'text-yellow-400' : 'text-green-400');
            $typeColor = match ($req['type'] ?? '') {
                'live' => 'text-purple-400',
                'ajax' => 'text-blue-400',
                default => 'text-gray-400',
            };

            $row = Element::make('div')
                ->class('cursor-pointer', 'hover:bg-gray-750')
                ->liveAction('toggleRequest', 'click', ['index' => $idx]);

            $cols = Element::make('div')->class('flex', 'px-4', 'py-2', 'border-b', 'border-gray-700', 'text-xs');
            $cols->child(Element::make('div')->class('w-16', $typeColor)->text($req['type'] ?? '-'));
            $cols->child(Element::make('div')->class('w-16')->text($req['method'] ?? '-'));
            $cols->child(Element::make('div')->class('w-20', $statusColor)->text((string)($status)));
            $cols->child(Element::make('div')->class('flex-1', 'truncate')->text($req['url'] ?? '-'));
            $cols->child(Element::make('div')->class('w-20')->text($req['duration'] ?? '-'));
            $cols->child(Element::make('div')->class('w-16')->text($req['time'] ?? '-'));
            $row->child($cols);

            if ($isSelected) {
                $detail = Element::make('div')->class('px-4', 'py-3', 'bg-gray-850', 'border-b', 'border-gray-700', 'space-y-2');
                $detail->child(Element::make('div')->class('text-xs', 'text-gray-400', 'font-semibold')->text('请求详情'));

                $requestBody = $req['requestBody'] ?? [];
                if (!empty($requestBody)) {
                    $detail->child(
                        Element::make('pre')->class('text-xs', 'text-green-300', 'bg-gray-900', 'p-2', 'rounded', 'overflow-x-auto')
                            ->text(json_encode($requestBody, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))
                    );
                }

                $responseSummary = $req['responseSummary'] ?? [];
                if (!empty($responseSummary)) {
                    $detail->child(Element::make('div')->class('text-xs', 'text-gray-400', 'font-semibold', 'mt-2')->text('响应摘要'));
                    $detail->child(
                        Element::make('pre')->class('text-xs', 'text-blue-300', 'bg-gray-900', 'p-2', 'rounded', 'overflow-x-auto')
                            ->text(json_encode($responseSummary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))
                    );
                }

                if (empty($requestBody) && empty($responseSummary)) {
                    $detail->child(Element::make('div')->class('text-xs', 'text-gray-500')->text('无详细数据'));
                }

                $row->child($detail);
            }

            $table->child($row);
        }

        $parent->child($table);
    }

    protected function renderSqlTab(Element $parent, array $snapshot): void
    {
        $queries = $snapshot['panels']['sql']['data']['queries'] ?? [];

        if (empty($queries)) {
            $parent->child(Element::make('div')->class('text-gray-500')->text('暂无 SQL 查询'));
            return;
        }

        $totalTime = $snapshot['panels']['sql']['data']['total_time'] ?? 0;
        $totalTime = is_numeric($totalTime) ? round((float)$totalTime, 2) . 'ms' : (string)$totalTime;

        $parent->child(
            Element::make('div')->class('text-xs', 'text-gray-400', 'mb-3')
                ->text("共 {$this->sqlCount} 条查询，总耗时 {$totalTime}")
        );

        $list = Element::make('div')->class('debug-reverse-list');

        foreach ($queries as $q) {
            $sql = is_array($q) ? ($q['sql'] ?? '') : '';
            $bindings = is_array($q) ? ($q['bindings'] ?? []) : [];
            $time = is_array($q) ? (float)($q['time'] ?? 0) : 0;
            $isSlow = $time > 100;

            $classes = ['bg-gray-800', 'rounded-lg', 'p-3', 'mb-2'];
            if ($isSlow) {
                $classes[] = 'border-l-2';
                $classes[] = 'border-red-500';
            }
            $card = Element::make('div')->class(...$classes);

            $top = Element::make('div')->class('flex', 'items-center', 'justify-between', 'mb-1');
            $top->child(Element::make('span')->class('text-xs', $isSlow ? 'text-red-400' : 'text-gray-400')
                ->text($time . 'ms' . ($isSlow ? ' ⚠' : '')));
            $card->child($top);

            $card->child(
                Element::make('div')->class('text-xs', 'text-green-300', 'font-mono', 'whitespace-pre-wrap', 'break-all')
                    ->text($sql)
            );

            if (!empty($bindings)) {
                $card->child(
                    Element::make('div')->class('text-xs', 'text-gray-500', 'mt-1')
                        ->text('Bindings: ' . json_encode($bindings, JSON_UNESCAPED_UNICODE))
                );
            }

            $list->child($card);
        }

        $parent->child($list);
    }

    protected function renderDebugTab(Element $parent, array $snapshot): void
    {
        $debugItems = $snapshot['debug'] ?? [];

        if (empty($debugItems)) {
            $parent->child(Element::make('div')->class('text-gray-500')->text('暂无调试数据（使用 debug() 函数添加）'));
            return;
        }

        $list = Element::make('div')->class('debug-reverse-list');

        foreach ($debugItems as $item) {
            $file = $item['file'] ?? '';
            $line = $item['line'] ?? '';
            $data = $item['data'] ?? [];

            $card = Element::make('div')->class('bg-gray-800', 'rounded-lg', 'p-3', 'mb-2');
            $card->child(
                Element::make('div')->class('text-xs', 'text-blue-400', 'mb-1')
                    ->text($file . ':' . $line)
            );

            foreach ($data as $val) {
                $dump = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $card->child(
                    Element::make('pre')->class('text-xs', 'text-gray-200', 'bg-gray-900', 'p-2', 'rounded', 'overflow-x-auto')
                        ->text($dump ?: 'null')
                );
            }

            $list->child($card);
        }

        $parent->child($list);
    }

    protected function renderMessagesTab(Element $parent, array $snapshot): void
    {
        $messages = $snapshot['messages'] ?? [];

        if (empty($messages)) {
            $parent->child(Element::make('div')->class('text-gray-500')->text('暂无消息'));
            return;
        }

        $list = Element::make('div')->class('debug-reverse-list');

        foreach ($messages as $msg) {
            $level = $msg['level'] ?? 'info';
            $textColor = match ($level) {
                'error' => 'text-red-300',
                'warning' => 'text-yellow-300',
                'success' => 'text-green-300',
                default => 'text-blue-300',
            };
            $bgColor = match ($level) {
                'error' => 'bg-red-900',
                'warning' => 'bg-yellow-900',
                'success' => 'bg-green-900',
                default => 'bg-blue-900',
            };

            $card = Element::make('div')
                ->class('rounded-lg', 'p-3', 'mb-1', 'flex', 'items-center', 'gap-3', $bgColor);

            $card->child(Element::make('span')->class('text-xs', 'text-gray-500')->text($msg['time'] ?? ''));
            $card->child(Element::make('span')->class('text-xs', $textColor)->text($msg['message'] ?? ''));

            $list->child($card);
        }

        $parent->child($list);
    }

    protected function renderRoutesTab(Element $parent, array $snapshot): void
    {
        $routeData = $snapshot['panels']['route']['data'] ?? [];

        if (empty($routeData)) {
            $parent->child(Element::make('div')->class('text-gray-500')->text('暂无路由信息'));
            return;
        }

        $table = Element::make('div')->class('bg-gray-800', 'rounded-lg', 'overflow-hidden');

        $header = Element::make('div')->class('flex', 'bg-gray-700', 'px-4', 'py-2', 'text-xs', 'text-gray-400', 'font-semibold');
        $header->child(Element::make('div')->class('w-24')->text('属性'));
        $header->child(Element::make('div')->class('flex-1')->text('值'));
        $table->child($header);

        foreach ($routeData as $key => $value) {
            $row = Element::make('div')->class('flex', 'px-4', 'py-2', 'border-b', 'border-gray-700', 'text-xs');
            $row->child(Element::make('div')->class('w-24', 'text-gray-400')->text($key));
            $row->child(Element::make('div')->class('flex-1', 'text-gray-200')->text(is_array($value) ? json_encode($value) : (string)$value));
            $table->child($row);
        }

        $parent->child($table);
    }

    protected function renderSessionTab(Element $parent, array $snapshot): void
    {
        $sessionData = $snapshot['panels']['session']['data']['data'] ?? [];

        if (empty($sessionData)) {
            $parent->child(Element::make('div')->class('text-gray-500')->text('暂无会话数据'));
            return;
        }

        $table = Element::make('div')->class('bg-gray-800', 'rounded-lg', 'overflow-hidden');

        $header = Element::make('div')->class('flex', 'bg-gray-700', 'px-4', 'py-2', 'text-xs', 'text-gray-400', 'font-semibold');
        $header->child(Element::make('div')->class('w-32')->text('Key'));
        $header->child(Element::make('div')->class('w-16')->text('类型'));
        $header->child(Element::make('div')->class('flex-1')->text('值'));
        $table->child($header);

        foreach ($sessionData as $item) {
            $row = Element::make('div')->class('flex', 'px-4', 'py-2', 'border-b', 'border-gray-700', 'text-xs');
            $row->child(Element::make('div')->class('w-32', 'text-blue-300', 'truncate')->text($item['key'] ?? ''));
            $row->child(Element::make('div')->class('w-16', 'text-gray-500')->text($item['type'] ?? ''));
            $row->child(Element::make('div')->class('flex-1', 'text-gray-200', 'truncate')->text($item['value'] ?? ''));
            $table->child($row);
        }

        $parent->child($table);
    }

    protected function renderPhpTab(Element $parent, array $snapshot): void
    {
        $php = $snapshot['php'] ?? [];

        $table = Element::make('div')->class('bg-gray-800', 'rounded-lg', 'overflow-hidden');

        $phpInfo = [
            ['PHP 版本', $php['version'] ?? PHP_VERSION],
            ['服务器', $php['server'] ?? $_SERVER['SERVER_SOFTWARE'] ?? '-'],
            ['请求方法', $php['method'] ?? $_SERVER['REQUEST_METHOD'] ?? '-'],
            ['请求 URL', $php['url'] ?? $_SERVER['REQUEST_URI'] ?? '-'],
            ['客户端 IP', $php['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '-'],
            ['时间', $php['time'] ?? date('Y-m-d H:i:s')],
            ['内存峰值', $snapshot['summary']['memory_peak'] ?? '-'],
            ['Debug Key', $snapshot['key'] ?? '-'],
        ];

        $header = Element::make('div')->class('flex', 'bg-gray-700', 'px-4', 'py-2', 'text-xs', 'text-gray-400', 'font-semibold');
        $header->child(Element::make('div')->class('w-32')->text('属性'));
        $header->child(Element::make('div')->class('flex-1')->text('值'));
        $table->child($header);

        foreach ($phpInfo as [$key, $value]) {
            $row = Element::make('div')->class('flex', 'px-4', 'py-2', 'border-b', 'border-gray-700', 'text-xs');
            $row->child(Element::make('div')->class('w-32', 'text-gray-400')->text($key));
            $row->child(Element::make('div')->class('flex-1', 'text-gray-200')->text($value));
            $table->child($row);
        }

        $parent->child($table);
    }
}

