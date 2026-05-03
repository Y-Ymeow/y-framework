<?php

declare(strict_types=1);

namespace Framework\DebugBar\Components;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveListener;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\DebugBar\DebugBar;
use Framework\DebugBar\SqlCollector;
use Framework\DebugBar\RouteCollector;
use Framework\DebugBar\RequestCollector;
use Framework\UX\Display\Badge;
use Framework\UX\UI\Accordion;
use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;

class DebugBarComponent extends LiveComponent
{
    public bool $expanded = false;
    public string $activeTab = 'php';
    public array $snapshot = [];

    public function mount(): void
    {
        $dbar = DebugBar::getInstance();
        $this->snapshot = $dbar->getSnapshot();

        // 如果 snapshot 为空（首次加载），尝试收集当前数据
        if (empty($this->snapshot)) {
            $this->collectDebugData();
        }

        $this->refresh('db-container');
    }

    #[LiveAction]
    #[LiveListener('debugbar:update')]
    public function refreshData(): void
    {
        $this->collectDebugData();
        $this->refresh('db-container');
    }

    private function collectDebugData(): void
    {
        // 尝试收集数据，但容忍数据库未初始化的情况
        try {
            $conn = \Framework\Database\Connection::get();
            SqlCollector::register();
        } catch (\Throwable $e) {
            // 数据库未初始化，跳过 SQL 收集
        }

        RouteCollector::register();
        RequestCollector::register();

        $dbar = DebugBar::getInstance();
        $dbar->collect();
        $this->snapshot = $dbar->getSnapshot();
    }

    #[LiveAction]
    public function toggle(): void
    {
        $this->expanded = !$this->expanded;
        $this->refresh('db-container');
    }

    #[LiveAction]
    public function selectTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->refresh('db-content');
    }

    public function render(): string|Element
    {
        AssetRegistry::getInstance()->ux();
        AssetRegistry::getInstance()->ui();

        $el = Element::make('div')->id('debugbar-native');
        $el->liveFragment('db-container');
        $el->attr('data-effect', "document.body.style.paddingBottom = expanded ? '436px' : '36px'");
        $el->style('position:fixed;bottom:0;left:0;right:0;z-index:999999;font-family:sans-serif;background:#1e1e1e;color:#d4d4d4;border-top:1px solid #333;box-shadow:0 -2px 10px rgba(0,0,0,0.5)');

        $el->child($this->renderStyles());
        $el->child($this->renderBar());

        if ($this->expanded) {
            $el->child($this->renderContentArea());
        }

        return $el->requireScript('debug-bar');
    }

    protected function renderStyles(): Element
    {
        return Element::make('style')->html(<<<CSS
#debugbar-native .db-bar{display:flex;align-items:center;height:35px;background:#1e1e1e}
#debugbar-native .db-bar:hover{background:#252526}
#debugbar-native .db-summary-item{padding:0 10px;font-size:12px;color:#4ec9b0;border-right:1px solid #333;height:100%;display:flex;align-items:center}
#debugbar-native .db-nav{background:#252526;border-bottom:1px solid #333;display:flex;list-style:none;margin:0;padding:0;height:35px}
#debugbar-native .db-nav-item{margin:0;display:flex}
#debugbar-native .db-nav-link{color:#888;padding:0 16px;border:none;border-right:1px solid #333;background:transparent;cursor:pointer;font-size:12px;height:100%;display:flex;align-items:center}
#debugbar-native .db-nav-item.active .db-nav-link{color:#fff;background:#007acc}
#debugbar-native pre{font-family:monospace;white-space:pre-wrap;word-break:break-all;margin:0}
#debugbar-native .req-row{display:flex;gap:15px;width:100%;align-items:center;font-size:12px}
#debugbar-native .req-method{color:#569cd6;font-weight:bold;min-width:45px}
#debugbar-native .req-url{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#9cdcfe}
#debugbar-native .req-status{font-weight:bold;width:35px;text-align:center}
#debugbar-native .req-duration{color:#b5cea8;width:60px;text-align:right}
#debugbar-native .db-table{width:100%;font-size:12px;border-collapse:collapse}
#debugbar-native .db-table th{text-align:left;padding:8px;width:150px;color:#9cdcfe;background:#252526}
#debugbar-native .db-table td{padding:8px}
#debugbar-native .db-table tr{border-bottom:1px solid #2d2d2d}
#debugbar-native .db-sql-time{color:#b5cea8;vertical-align:top;width:80px}
#debugbar-native .db-sql-query{color:#ce9178;font-family:monospace;word-break:break-all}
#debugbar-native .db-sql-bindings{color:#888;margin-top:4px}
#debugbar-native .db-msg-row{display:flex;gap:10px;padding:8px 0;border-bottom:1px solid #2d2d2d;font-size:12px}
#debugbar-native .db-msg-time{color:#888}
#debugbar-native .db-msg-level{font-weight:bold;width:70px}
#debugbar-native .db-debug-block{margin-bottom:15px;border:1px solid #333;border-radius:4px}
#debugbar-native .db-debug-header{background:#252526;padding:4px 10px;font-size:10px;color:#888}
#debugbar-native .db-debug-body{padding:8px;overflow:auto}
#debugbar-native .db-debug-pre{margin:0;color:#ce9178;font-size:11px}
#debugbar-native .db-sql-summary{background:#252526;padding:8px;margin-bottom:10px;border-radius:4px;font-size:11px;color:#4ec9b0}
CSS);
    }

    protected function renderBar(): Element
    {
        $bar = Element::make('div')->class('db-bar');
        if ($this->expanded) {
            $bar->style('border-bottom:1px solid #333');
        }

        $bar->child(
            Element::make('div')->class('db-summary-item')->html('⏱ ' . ($this->snapshot['summary']['duration'] ?? '0ms'))
        );
        $bar->child(
            Element::make('div')->class('db-summary-item')->html('💾 ' . ($this->snapshot['summary']['memory'] ?? '0B'))
        );

        $toggle = Element::make('div')
            ->style('margin-left:auto;padding:0 15px;cursor:pointer;color:#888;border-left:1px solid #333;height:100%;display:flex;align-items:center')
            ->text($this->expanded ? '▼' : '▲')
            ->liveAction('toggle');
        $bar->child($toggle);

        return $bar;
    }

    protected function renderContentArea(): Element
    {
        $contentArea = Element::make('div')
            ->id('db-content-area')
            ->style('height:400px;display:flex;flex-direction:column;background:#1e1e1e');
        $contentArea->liveFragment('db-content');

        $contentArea->child($this->renderNav());

        $pane = Element::make('div')->style('flex:1;overflow:hidden');
        $content = match ($this->activeTab) {
            'php' => $this->renderPhpPanel(),
            'request' => $this->renderRequestPanel($this->snapshot['panels']['request']['data'] ?? []),
            'sql' => $this->renderSqlPanel($this->snapshot['panels']['sql']['data'] ?? []),
            'debug' => $this->renderDebugPanel($this->snapshot['debug'] ?? []),
            'messages' => $this->renderMessagesPanel($this->snapshot['messages'] ?? []),
            'routes' => $this->renderRoutePanel($this->snapshot['panels']['routes']['data'] ?? []),
            default => Element::make('div')->text('Select a tab'),
        };

        if ($content instanceof Element) {
            $pane->child($content);
        } else {
            $pane->html((string)$content);
        }

        $contentArea->child($pane);
        return $contentArea;
    }

    protected function renderNav(): Element
    {
        $nav = Element::make('ul')->class('db-nav');
        $tabs = ['php' => 'PHP', 'request' => 'Requests', 'sql' => 'SQL', 'debug' => 'Debug', 'messages' => 'Messages', 'routes' => 'Routes'];

        foreach ($tabs as $id => $label) {
            $item = Element::make('li')->class('db-nav-item');
            if ($this->activeTab === $id) {
                $item->class('active');
            }

            $link = Element::make('button')
                ->class('db-nav-link')
                ->attr('type', 'button')
                ->liveAction('selectTab')
                ->liveParams($this->signedAction('selectTab', ['tab' => $id]));

            $badgeValue = match ($id) {
                'request' => $this->snapshot['panels']['request']['data']['total'] ?? 0,
                'sql' => $this->snapshot['panels']['sql']['data']['total_queries'] ?? 0,
                'debug' => count($this->snapshot['debug'] ?? []),
                'messages' => count($this->snapshot['messages'] ?? []),
                default => null
            };

            $link->text($label);
            if ($badgeValue > 0) {
                $link->child(' ');
                $badgeHtml = Badge::make((string)$badgeValue)->primary()->sm()->render();
                $link->child($badgeHtml);
            }

            $item->child($link);
            $nav->child($item);
        }

        return $nav;
    }

    protected function renderPhpPanel(): Element
    {
        $php = $this->snapshot['php'] ?? [];
        $wrapper = Element::make('div')->style('padding:10px;overflow:auto;height:360px');
        $table = Element::make('table')->class('db-table');

        foreach ($php as $key => $val) {
            $tr = Element::make('tr');
            $tr->child(Element::make('th')->text(ucfirst($key)));
            $tr->child(Element::make('td')->text((string)$val));
            $table->child($tr);
        }

        $wrapper->child($table);
        return $wrapper;
    }

    protected function renderRequestPanel(array $data): Element
    {
        $history = $data['history'] ?? [];
        $wrapper = Element::make('div')->style('height:360px;overflow:auto;padding:10px');

        if (empty($history)) {
            $wrapper->child(Element::make('div')->style('padding:20px;color:#888')->text('No requests captured'));
            return $wrapper;
        }

        $accordion = Accordion::make()->dark();
        foreach ($history as $i => $req) {
            $status = (int)($req['status'] ?? 200);
            $statusColor = $status >= 400 ? '#f85149' : ($status >= 300 ? '#d29922' : '#3fb950');

            $titleEl = Element::make('div')->class('req-row');
            $titleEl->child(Element::make('span')->class('req-method')->text($req['method'] ?? 'GET'));
            $titleEl->child(Element::make('span')->class('req-url')->text(htmlspecialchars($req['url'] ?? '/')));
            $titleEl->child(Element::make('span')->class('req-status')->style("color:{$statusColor}")->text((string)$status));
            $titleEl->child(Element::make('span')->class('req-duration')->text($req['duration'] ?? '0ms'));

            $contentEl = Element::make('div')->style('padding:10px;background:#252526;font-size:11px');
            $contentEl->child(
                Element::make('pre')->style('margin:0;color:#ce9178')->html(htmlspecialchars(json_encode($req, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)))
            );

            $accordion->item($titleEl->render(), $contentEl->render(), "req-$i");
        }

        $wrapper->html($accordion->render());
        return $wrapper;
    }

    protected function renderSqlPanel(array $data): Element
    {
        $queries = $data['queries'] ?? [];
        $wrapper = Element::make('div')->style('padding:10px;overflow:auto;height:360px');

        if (empty($queries)) {
            $wrapper->child(Element::make('div')->style('padding:20px;color:#888')->text('No SQL queries'));
            return $wrapper;
        }

        $summary = Element::make('div')->class('db-sql-summary');
        $summary->text('Total: ' . $data['total_queries'] . ' queries | Time: ' . $data['total_time']);
        $wrapper->child($summary);

        $table = Element::make('table')->class('db-table');

        foreach ($queries as $q) {
            $tr = Element::make('tr');

            $tdTime = Element::make('td')->class('db-sql-time')->text($q['time']);
            $tr->child($tdTime);

            $tdQuery = Element::make('td')->class('db-sql-query');
            $tdQuery->text(htmlspecialchars($q['sql']));

            if (!empty($q['bindings'])) {
                $bindingsStr = implode(', ', array_map(fn($b) => is_string($b) ? "'$b'" : (is_array($b) ? json_encode($b) : (string)$b), $q['bindings']));
                $tdQuery->child(Element::make('div')->class('db-sql-bindings')->text('[' . $bindingsStr . ']'));
            }

            $tr->child($tdQuery);
            $table->child($tr);
        }

        $wrapper->child($table);
        return $wrapper;
    }

    protected function renderDebugPanel(array $data): Element
    {
        $wrapper = Element::make('div')->style('padding:10px;overflow:auto;height:360px');

        if (empty($data)) {
            $wrapper->child(Element::make('div')->style('padding:20px;color:#888')->text('No debug data'));
            return $wrapper;
        }

        foreach ($data as $i => $call) {
            $block = Element::make('div')->class('db-debug-block');
            $block->child(Element::make('div')->class('db-debug-header')->children(
                Element::make('span')->text('Call #' . ($i + 1)),
                Element::make("div")
                    ->style("float:right;color:#fe7e7e;user-select: none;")
                    ->children(
                        Element::make('span')->text(htmlspecialchars($call['file'] . ' : ')),
                        Element::make('span')->text(htmlspecialchars((string)$call['line'])),
                    ),
            ));

            foreach ($call['data'] as $var) {
                $body = Element::make('div')->class('db-debug-body');
                $body->child(Element::make('pre')->class('db-debug-pre')->html(htmlspecialchars(print_r($var, true))));
                $block->child($body);
            }

            $wrapper->child($block);
        }

        return $wrapper;
    }

    protected function renderMessagesPanel(array $messages): Element
    {
        $wrapper = Element::make('div')->style('padding:10px;overflow:auto;height:360px');

        if (empty($messages)) {
            $wrapper->child(Element::make('div')->style('padding:20px;color:#888')->text('No messages'));
            return $wrapper;
        }

        foreach ($messages as $msg) {
            $level = $msg['level'] ?? 'info';
            $color = match ($level) {
                'error' => '#f85149',
                'warning' => '#d29922',
                'success' => '#3fb950',
                default => '#58a6ff',
            };

            $row = Element::make('div')->class('db-msg-row');
            $row->child(Element::make('span')->class('db-msg-time')->text('[' . $msg['time'] . ']'));
            $row->child(Element::make('span')->class('db-msg-level')->style("color:{$color}")->text(strtoupper($level)));
            $row->child(Element::make('span')->text(htmlspecialchars($msg['message'])));

            $wrapper->child($row);
        }

        return $wrapper;
    }

    protected function renderRoutePanel(array $data): Element
    {
        $wrapper = Element::make('div')->style('padding:10px;overflow:auto;height:360px');

        if (empty($data)) {
            $wrapper->child(Element::make('div')->style('padding:20px;color:#888')->text('No route information'));
            return $wrapper;
        }

        $table = Element::make('table')->class('db-table');
        $fields = [
            'Method' => $data['method'] ?? 'N/A',
            'URI' => $data['matched_route'] ?? 'N/A',
            'Controller' => $data['controller'] ?? 'N/A',
            'Parameters' => json_encode($data['parameters'] ?? [], JSON_PRETTY_PRINT),
        ];

        foreach ($fields as $key => $val) {
            $tr = Element::make('tr');
            $tr->child(Element::make('th')->text($key));
            $tr->child(Element::make('td')->child(Element::make('pre')->style('margin:0')->text((string)$val)));
            $table->child($tr);
        }

        $wrapper->child($table);
        return $wrapper;
    }
}
