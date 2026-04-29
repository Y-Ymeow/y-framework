<?php

declare(strict_types=1);

namespace Admin\Pages;

use Framework\Component\Attribute\LiveAction;
use Framework\Component\LiveComponent;
use Framework\Database\Connection;
use Framework\Routing\Attribute\Get;
use Framework\Routing\Attribute\Route;
use Framework\View\Base\Element;
use Framework\View\Container;
use Framework\View\Document\AssetRegistry;
use Framework\View\Text;
use Framework\View\Document\Document;
use Framework\View\LiveResponse;

class DemoLivePage
{
    private const FADE_UP_TRANSITION = <<<'JS'
{
    duration: 220,
    enter: {
        easing: 'ease-out',
        from: { opacity: '0', transform: 'translateY(8px)' },
        to: { opacity: '1', transform: 'translateY(0)' }
    },
    leave: {
        duration: 180,
        easing: 'ease-in',
        from: { opacity: '1', transform: 'translateY(0)' },
        to: { opacity: '0', transform: 'translateY(8px)' }
    }
}
JS;

    private const TOAST_TRANSITION = <<<'JS'
{
    enter: {
        duration: 240,
        easing: 'ease-out',
        from: { opacity: '0', transform: 'translateY(12px) scale(0.96)' },
        to: { opacity: '1', transform: 'translateY(0) scale(1)' }
    },
    leave: {
        duration: 180,
        easing: 'ease-in',
        from: { opacity: '1', transform: 'translateY(0) scale(1)' },
        to: { opacity: '0', transform: 'translateY(6px) scale(0.98)' }
    }
}
JS;


    #[Route('/demo')]
    public function index(): \Framework\Http\Response
    {
        $flagGen = new FlagGenerator();
        $flagGen->named('flag-gen');

        $htmlDemo = new HtmlFragmentDemo();
        $htmlDemo->named('html-demo');

        $listDemo = new VirtualListDemo();
        $listDemo->named('virtual-list');

        $dbDemo = new DatabaseFragmentDemo();
        $dbDemo->named('db-fragment-demo');

        AssetRegistry::getInstance()->ux();
        AssetRegistry::getInstance()->ui();

        $doc =
            Container::make()
            ->class('bg-gray-50 min-h-screen p-8')
            ->child(
                Container::make()
                    ->class('max-w-4xl mx-auto space-y-8')
                    ->child(Text::h1('Data Attributes Demo')->class('text-3xl font-bold mb-2'))
                    ->child(Text::p('data-on:* 监听事件 / $dispatch 触发事件 / data-state 管理状态')->textGray()->textSm())
                    ->child($this->renderClientDemo())
                    ->child($this->renderModalDemo())
                    ->child($flagGen)
                    ->child($htmlDemo)
                    ->child($listDemo)
                    ->child($dbDemo)
            );

        return \Framework\Http\Response::html($doc);
    }

    private function renderClientDemo(): Element
    {
        return Container::make()
            ->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6')
            ->state([
                'open' => true,
                'count' => 0,
                'tab' => 'info',
                'text' => '',
            ])
            ->child(Text::h2('纯客户端（data-state + data-text + data-on:click）')->class('text-lg font-semibold mb-4'))
            ->child(
                Container::make()->flex('row')->gap(2)->class('mb-4')
                    ->child(
                        (new Element('button'))
                            ->class('px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700')
                            ->bindOn('click', 'open = !open')
                            ->bindText("open ? '关闭面板' : '打开面板'")
                    )
                    ->child(
                        (new Element('button'))
                            ->class('px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700')
                            ->bindOn('click', 'count++')
                            ->text('计数 +1')
                    )
            )
            ->child(
                Container::make()
                    ->bindShow('open')
                    ->bindTransition(self::FADE_UP_TRANSITION)
                    ->class('bg-blue-50 border border-blue-200 rounded-lg p-4')
                    ->child(Text::p('这个面板由 data-show="open" 控制，完全客户端')->class('text-blue-800'))
                    ->child(
                        Container::make()->class('mt-2')
                            ->child((new Element('span'))->bindText('count')->class('font-bold text-blue-600'))
                            ->child((new Element('span'))->text(' 次点击')->class('text-blue-600 text-sm'))
                    )
            )
            ->child(
                Container::make()->class('border-t pt-4')
                    ->child(Text::p('Tab 切换（data-on:click + data-show + data-bind:class）：')->class('font-medium mb-2'))
                    ->child(
                        Container::make()->flex('row')->gap(2)->class('mb-3')
                            ->child(
                                (new Element('button'))
                                    ->class('px-3 py-1.5 rounded-md text-sm')
                                    ->dataClass("{'bg-blue-600 text-white': tab === 'info', 'bg-gray-100 text-gray-600': tab !== 'info'}")
                                    ->bindOn('click', "tab = 'info'")
                                    ->text('Info')
                            )
                            ->child(
                                (new Element('button'))
                                    ->class('px-3 py-1.5 rounded-md text-sm')
                                    ->dataClass("{'bg-blue-600 text-white': tab === 'settings', 'bg-gray-100 text-gray-600': tab !== 'settings'}")
                                    ->bindOn('click', "tab = 'settings'")
                                    ->text('Settings')
                            )
                            ->child(
                                (new Element('button'))
                                    ->class('px-3 py-1.5 rounded-md text-sm')
                                    ->dataClass("{'bg-blue-600 text-white': tab === 'stats', 'bg-gray-100 text-gray-600': tab !== 'stats'}")
                                    ->bindOn('click', "tab = 'stats'")
                                    ->text('Stats')
                            )
                    )
                    ->child(
                        Container::make()->bindShow("tab === 'info'")->bindTransition(self::FADE_UP_TRANSITION)->class('bg-gray-50 rounded p-3')
                            ->child(Text::p('Info 标签页内容')->class('text-gray-700'))
                    )
                    ->child(
                        Container::make()->bindShow("tab === 'settings'")->bindTransition(self::FADE_UP_TRANSITION)->class('bg-gray-50 rounded p-3')
                            ->child(Text::p('Settings 标签页内容')->class('text-gray-700'))
                    )
                    ->child(
                        Container::make()->bindShow("tab === 'stats'")->bindTransition(self::FADE_UP_TRANSITION)->class('bg-gray-50 rounded p-3')
                            ->child(Text::p('Stats - 计数: ')->class('text-gray-700 inline'))
                            ->child((new Element('span'))->bindText('count')->class('font-bold text-blue-600'))
                    )
            )
            ->child(
                Container::make()->class('border-t pt-4')
                    ->child(Text::p('双向绑定（data-model）：')->class('font-medium mb-2'))
                    ->child(
                        (new Element('input'))
                            ->bindModel('text')
                            ->attr('type', 'text')
                            ->attr('placeholder', '输入文字...')
                            ->class('w-full px-3 py-2 border rounded-md text-sm')
                    )
                    ->child(
                        Container::make()->class('mt-2')
                            ->child((new Element('span'))->text('实时预览: ')->class('text-sm text-gray-500'))
                            ->child((new Element('span'))->bindText('text || "(空)"')->class('text-sm font-medium'))
                    )
            );
    }

    private function renderModalDemo(): Element
    {
        return Container::make()
            ->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6')
            ->state([
                'showModal' => false,
                'showDrawer' => false,
                'toastMessage' => '',
                'toastType' => 'success',
                'showToast' => false,
            ])
            ->bindOn('toast:show', 'showToast = true; toastMessage = $event.detail.message; toastType = $event.detail.type || "success"; window.clearTimeout(window.__toastTimer); window.__toastTimer = window.setTimeout(() => $dispatch("toast:hide"), 3000)')
            ->bindOn('toast:hide', 'showToast = false; toastMessage = ""')
            ->child(Text::h2('$dispatch 触发 + data-on:* 监听')->class('text-lg font-semibold mb-4'))
            ->child(Text::p('按钮用 $dispatch 触发事件，Modal/Toast 用 data-on:* 监听事件')->textGray()->textSm()->class('mb-4'))
            ->child(
                Container::make()->flex('row')->gap(2)->class('mb-4')
                    ->child(
                        (new Element('button'))
                            ->class('px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700')
                            ->bindOn('click', '$dispatch("modal:open")')
                            ->text('打开 Modal')
                    )
                    ->child(
                        (new Element('button'))
                            ->class('px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700')
                            ->bindOn('click', '$dispatch("drawer:open")')
                            ->text('打开 Drawer')
                    )
                    ->child(
                        (new Element('button'))
                            ->class('px-4 py-2 bg-orange-600 text-white rounded-md text-sm hover:bg-orange-700')
                            ->bindOn('click', '$dispatch("toast:show", { message: "操作成功！", type: "success" })')
                            ->text('显示 Toast')
                    )
                    ->child(
                        (new Element('button'))
                            ->class('px-4 py-2 bg-red-600 text-white rounded-md text-sm hover:bg-red-700')
                            ->bindOn('click', '$dispatch("toast:show", { message: "出错了！", type: "error" })')
                            ->text('错误 Toast')
                    )
            )
            ->child(
                Container::make()
                    ->bindOn('modal:open', 'showModal = true')
                    ->bindOn('modal:close', 'showModal = false')
                    ->child(
                        Container::make()
                            ->bindShow('showModal')
                            ->bindTransition("{
                                enter: {
                                    duration: 200,
                                    from: { opacity: '0' },
                                    to: { opacity: '1' }
                                },
                                leave: {
                                    duration: 160,
                                    from: { opacity: '1' },
                                    to: { opacity: '0' }
                                }
                            }")
                            ->class('fixed inset-0 bg-black/50 flex items-center justify-center z-50')
                            ->attr('style', 'backdrop-filter:blur(4px)')
                            ->child(
                                Container::make()
                                    ->bindTransition(self::FADE_UP_TRANSITION)
                                    ->class('bg-white rounded-xl shadow-2xl p-6 max-w-md w-full mx-4')
                                    ->child(Text::h3('Modal 对话框')->class('text-lg font-semibold mb-3'))
                                    ->child(Text::p('这个 Modal 由 $dispatch 触发 modal:open/close 事件，data-on:modal:open/close 监听。')->class('text-gray-600 mb-4'))
                                    ->child(
                                        Container::make()->flex('row')->gap(2)->class('justify-end')
                                            ->child(
                                                (new Element('button'))
                                                    ->class('px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300')
                                                    ->bindOn('click', '$dispatch("modal:close")')
                                                    ->text('关闭')
                                            )
                                            ->child(
                                                (new Element('button'))
                                                    ->class('px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700')
                                                    ->bindOn('click', '$dispatch("modal:close")')
                                                    ->text('确认')
                                            )
                                    )
                            )
                    )
            )
            ->child(
                Container::make()
                    ->bindOn('drawer:open', 'showDrawer = true')
                    ->bindOn('drawer:close', 'showDrawer = false')
                    ->child(
                        Container::make()
                            ->bindShow('showDrawer')
                            ->bindTransition("{
                                enter: {
                                    duration: 200,
                                    from: { opacity: '0' },
                                    to: { opacity: '1' }
                                },
                                leave: {
                                    duration: 160,
                                    from: { opacity: '1' },
                                    to: { opacity: '0' }
                                }
                            }")
                            ->class('fixed inset-0 bg-black/50 z-50')
                            ->attr('style', 'backdrop-filter:blur(4px)')
                            ->bindOn('click.self', '$dispatch("drawer:close")')
                    )
                    ->child(
                        Container::make()
                            ->bindShow('showDrawer')
                            ->bindTransition("{
                                enter: {
                                    duration: 320,
                                    easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                                    from: { transform: 'translateX(100%)', opacity: '0.7' },
                                    to: { transform: 'translateX(0)', opacity: '1' }
                                },
                                leave: {
                                    duration: 240,
                                    easing: 'ease-in',
                                    from: { transform: 'translateX(0)', opacity: '1' },
                                    to: { transform: 'translateX(100%)', opacity: '0.85' }
                                }
                            }")
                            ->class('fixed right-0 top-0 z-[60] h-full w-80 bg-white shadow-2xl p-6')
                            ->child(
                                Text::h3('Drawer 抽屉')->class('text-lg font-semibold mb-3')
                            )
                            ->child(Text::p('现在这个抽屉本体会从右往左滑入，遮罩单独淡入。')->class('text-gray-600 mb-4'))
                            ->child(
                                (new Element('button'))
                                    ->class('px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300')
                                    ->bindOn('click', '$dispatch("drawer:close")')
                                    ->text('关闭')
                            )
                    )
            )
            ->child(
                Container::make()
                    ->bindShow('showToast')
                    ->bindTransition(self::TOAST_TRANSITION)
                    ->class('fixed bottom-4 right-4 z-50')
                    ->child(
                        Container::make()
                            ->dataClass("{'bg-green-500': toastType !== 'error', 'bg-red-500': toastType === 'error'}")
                            ->class('text-white px-4 py-3 rounded-lg shadow-lg text-sm flex items-center gap-2')
                            ->child((new Element('span'))->text('✓'))
                            ->child((new Element('span'))->bindText('toastMessage'))
                            ->child(
                                (new Element('button'))
                                    ->class('ml-2 text-white/70 hover:text-white')
                                    ->bindOn('click', '$dispatch("toast:hide")')
                                    ->text('×')
                            )
                    )
            );
    }
}

class DatabaseFragmentDemo extends LiveComponent
{
    public array $rows = [];
    public int $total = 0;
    public int $done = 0;

    public function mount(): void
    {
        $this->ensureTable();
        $this->seedIfEmpty();
        $this->reload();
    }

    #[LiveAction]
    public function addRow(): LiveResponse
    {
        $this->db()->insert('demo_tasks', [
            'title' => 'Task ' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->reload();

        return LiveResponse::make()
            ->fragment('db-stats', $this->renderStatsHtml())
            ->fragment('db-list', $this->renderListHtml())
            ->fragment('db-summary', $this->renderSummaryHtml())
            ->dispatch('toast:show', null, ['message' => '已插入一条 SQLite 数据', 'type' => 'success']);
    }

    #[LiveAction]
    public function toggleRow(string $id): LiveResponse
    {
        $row = $this->db()->table('demo_tasks')->find((int) $id);
        if (!$row) {
            return LiveResponse::make();
        }

        $nextStatus = ($row['status'] ?? 'pending') === 'done' ? 'pending' : 'done';
        $this->db()->table('demo_tasks')->where('id', (int) $id)->update([
            'status' => $nextStatus,
        ]);

        $this->reload();

        return LiveResponse::make()
            ->fragment('db-stats', $this->renderStatsHtml())
            ->fragment('db-row-' . $id, $this->renderRowInnerHtml($this->findRow((int) $id) ?? $row))
            ->fragment('db-summary', $this->renderSummaryHtml())
            ->dispatch('toast:show', null, ['message' => '已执行多分片更新', 'type' => 'success']);
    }

    #[LiveAction]
    public function deleteRow(string $id): LiveResponse
    {
        $this->db()->table('demo_tasks')->where('id', (int) $id)->delete();
        $this->reload();

        return LiveResponse::make()
            ->remove('#db-row-' . (int) $id)
            ->fragment('db-stats', $this->renderStatsHtml())
            ->fragment('db-summary', $this->renderSummaryHtml())
            ->dispatch('db:item:deleted', null, ['itemId' => 'db-row-' . (int) $id])
            ->dispatch('toast:show', null, ['message' => '已删除并刷新相关分片', 'type' => 'success']);
    }

    #[LiveAction]
    public function resetRows(): LiveResponse
    {
        $this->db()->execute('DELETE FROM demo_tasks');
        $this->seedIfEmpty();
        $this->reload();

        return LiveResponse::make()
            ->fragment('db-stats', $this->renderStatsHtml())
            ->fragment('db-list', $this->renderListHtml())
            ->fragment('db-summary', $this->renderSummaryHtml())
            ->dispatch('toast:show', null, ['message' => '已重置数据库分片', 'type' => 'success']);
    }

    public function render(): string|Element
    {
        return Container::make()
            ->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6')
            ->child(Text::h2('SQLite + 多分片 Live 更新')->class('text-lg font-semibold mb-2'))
            ->child(Text::p('同一个 action 同时更新数据库统计、列表内容和摘要分片。')->textGray()->textSm()->class('mb-4'))
            ->child(
                Container::make()->flex('row')->gap(2)->class('mb-4')
                    ->child(
                        (new Element('button'))
                            ->liveAction('addRow')
                            ->class('px-4 py-2 bg-emerald-600 text-white rounded-md text-sm hover:bg-emerald-700')
                            ->text('新增一条')
                    )
                    ->child(
                        (new Element('button'))
                            ->liveAction('resetRows')
                            ->class('px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300')
                            ->text('重置 SQLite 示例')
                    )
            )
            ->child(
                Container::make()
                    ->liveFragment('db-stats')
                    ->html($this->renderStatsHtml())
            )
            ->child(
                Container::make()
                    ->liveFragment('db-list')
                    ->class('mt-4 space-y-2')
                    ->html($this->renderListHtml())
            )
            ->child(
                Container::make()
                    ->liveFragment('db-summary')
                    ->class('mt-4')
                    ->html($this->renderSummaryHtml())
            );
    }

    private function renderStatsHtml(): string
    {
        return '<div class="grid grid-cols-3 gap-3">'
            . $this->statCard('总数', (string) $this->total, 'text-blue-600')
            . $this->statCard('已完成', (string) $this->done, 'text-emerald-600')
            . $this->statCard('待处理', (string) max(0, $this->total - $this->done), 'text-orange-600')
            . '</div>';
    }

    private function statCard(string $label, string $value, string $valueClass): string
    {
        return '<div class="rounded-lg border border-gray-200 bg-gray-50 p-4">'
            . '<div class="text-sm text-gray-500">' . htmlspecialchars($label) . '</div>'
            . '<div class="mt-1 text-2xl font-bold ' . $valueClass . '">' . htmlspecialchars($value) . '</div>'
            . '</div>';
    }

    private function renderListHtml(): string
    {
        if ($this->rows === []) {
            return '<div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500">SQLite 示例表当前没有数据。</div>';
        }

        $html = '';
        foreach ($this->rows as $row) {
            $html .= '<div id="db-row-' . (int) $row['id'] . '" data-live-fragment="db-row-' . (int) $row['id'] . '" class="rounded-lg border border-gray-200 bg-white">';
            $html .= $this->renderRowInnerHtml($row);
            $html .= '</div>';
        }

        return $html;
    }

    private function renderRowInnerHtml(array $row): string
    {
        $id = (int) $row['id'];
        $isDone = ($row['status'] ?? 'pending') === 'done';
        $badgeClass = $isDone ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700';
        $toggleClass = $isDone ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-blue-100 text-blue-700 hover:bg-blue-200';
        $toggleLabel = $isDone ? '标记待处理' : '标记完成';
        $toggleParams = htmlspecialchars($this->encodeActionParams('toggleRow', ['id' => (string) $id]), ENT_QUOTES, 'UTF-8');
        $deleteParams = htmlspecialchars($this->encodeActionParams('deleteRow', ['id' => (string) $id]), ENT_QUOTES, 'UTF-8');

        return '<div class="flex items-center justify-between p-4">'
            . '<div class="min-w-0">'
            . '<div class="font-medium text-gray-900">' . htmlspecialchars((string) $row['title']) . '</div>'
            . '<div class="mt-1 text-xs text-gray-500">SQLite Row #' . $id . ' · ' . htmlspecialchars((string) ($row['created_at'] ?? '')) . '</div>'
            . '</div>'
            . '<div class="ml-4 flex items-center gap-2">'
            . '<span class="rounded-full px-2 py-1 text-xs font-medium ' . $badgeClass . '">' . ($isDone ? 'done' : 'pending') . '</span>'
            . '<button data-action="toggleRow" data-action-params=\'' . $toggleParams . '\' class="rounded px-3 py-1.5 text-xs ' . $toggleClass . '"> ' . $toggleLabel . ' </button>'
            . '<button data-action="deleteRow" data-action-params=\'' . $deleteParams . '\' class="rounded bg-red-100 px-3 py-1.5 text-xs text-red-700 hover:bg-red-200">删除</button>'
            . '</div>'
            . '</div>';
    }

    private function renderSummaryHtml(): string
    {
        $latest = $this->rows[0]['title'] ?? '暂无记录';
        return '<div class="rounded-lg border border-gray-200 bg-slate-50 p-4 text-sm text-gray-600">'
            . '最近一条任务：<span class="font-medium text-gray-900">' . htmlspecialchars((string) $latest) . '</span>'
            . '，当前数据库文件：<span class="font-mono text-xs">' . htmlspecialchars($this->dbFile()) . '</span>'
            . '</div>';
    }

    private function reload(): void
    {
        $this->rows = $this->db()->table('demo_tasks')->latest('id')->get();
        $this->total = count($this->rows);
        $this->done = count(array_filter($this->rows, fn(array $row) => ($row['status'] ?? 'pending') === 'done'));
    }

    private function findRow(int $id): ?array
    {
        foreach ($this->rows as $row) {
            if ((int) ($row['id'] ?? 0) === $id) {
                return $row;
            }
        }

        return null;
    }

    private function ensureTable(): void
    {
        $this->db()->execute('CREATE TABLE IF NOT EXISTS demo_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT "pending",
            created_at TEXT NOT NULL
        )');
    }

    private function seedIfEmpty(): void
    {
        $count = $this->db()->table('demo_tasks')->count();
        if ($count > 0) {
            return;
        }

        foreach (['Import seed data', 'Warm cache', 'Render dashboard'] as $index => $title) {
            $this->db()->insert('demo_tasks', [
                'title' => $title,
                'status' => $index === 0 ? 'done' : 'pending',
                'created_at' => date('Y-m-d H:i:s', time() - ($index * 120)),
            ]);
        }
    }

    private function db(): Connection
    {
        static $connection = null;
        if ($connection instanceof Connection) {
            return $connection;
        }

        $connection = db();

        return $connection;
    }

    private function dbFile(): string
    {
        return base_path('database/demo-live.sqlite');
    }
}

class FlagGenerator extends LiveComponent
{
    public string $flagCode = '';
    public int $count = 0;

    #[LiveAction]
    public function generate(): LiveResponse
    {
        $this->flagCode = 'FLAG-' . strtoupper(bin2hex(random_bytes(8)));
        $this->count++;
        return LiveResponse::make()->toast('Flag 已生成');
    }

    #[LiveAction]
    public function reset(): LiveResponse
    {
        $this->flagCode = '';
        $this->count = 0;
        return LiveResponse::make()->toast('已重置');
    }

    public function render(): string|Element
    {
        return
            Container::make()->children(

                Container::make()->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col gap-3')
                    ->state(['locale' => 'zh'])
                    ->children(
                        Element::make('button')
                            ->class('px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700')
                            ->data('on:click', 'locale = locale === "zh" ? "en" : "zh"; $locale(locale)')
                            ->text('切换翻译'),
                        Element::make('button')
                            ->class('px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700')
                            ->data('on:click', 'locale = "zh"; $locale(locale)')
                            ->text('switch translate'),
                        Element::make('span')
                            ->intl('messages.welcome')
                            ->text('欢迎使用我们的应用程序！')
                    ),

                Container::make()
                    ->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6')
                    ->child(Text::h2('Live Component（data-live + data-model + data-action）')->class('text-lg font-semibold mb-2'))
                    ->child(Text::p('服务器返回 state patches，客户端响应式自动更新 DOM：')->textGray()->textSm()->class('mb-4'))
                    ->child(
                        Container::make()->class('space-y-4')
                            ->child(
                                Container::make()->flex('row')->gap(4)->class('items-center')
                                    ->child(
                                        Container::make()
                                            ->child(Text::p('已生成次数：')->class('text-sm text-gray-500'))
                                            ->child((new Element('span'))->data('text', 'count')->class('text-2xl font-bold text-blue-600')->text((string)$this->count))
                                    )
                                    ->child(
                                        Container::make()
                                            ->child(Text::p('Flag 代码：')->class('text-sm text-gray-500'))
                                            ->child((new Element('span'))->data('text', "flagCode ? flagCode : '(未生成)' ")->class('text-lg font-mono font-bold text-green-600')->text($this->flagCode ?: '(未生成)'))
                                    )
                            )
                            ->child(
                                Container::make()->flex('row')->gap(2)
                                    ->child(
                                        (new Element('button'))
                                            ->liveAction('generate')
                                            ->class('px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700')
                                            ->text('生成 Flag')
                                    )
                                    ->child(
                                        (new Element('button'))
                                            ->liveAction('reset')
                                            ->class('px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300')
                                            ->text('重置')
                                    )
                            )
                    )

            );
    }
}

class HtmlFragmentDemo extends LiveComponent
{
    public array $items = [];
    public int $refreshCount = 0;

    public function mount(): void
    {
        $this->items = $this->generateItems();
    }

    private function generateItems(): array
    {
        $names = ['Apple', 'Banana', 'Cherry', 'Durian', 'Elderberry', 'Fig', 'Grape', 'Honeydew'];
        $statuses = ['pending', 'completed', 'processing'];
        $items = [];
        for ($i = 0; $i < 5; $i++) {
            $items[] = [
                'id' => uniqid(),
                'name' => $names[array_rand($names)] . ' ' . rand(100, 999),
                'status' => $statuses[array_rand($statuses)],
                'price' => rand(10, 100) . '.' . rand(10, 99),
            ];
        }
        return $items;
    }

    #[LiveAction]
    public function refreshList(): LiveResponse
    {
        $this->refreshCount++;
        $this->items = $this->generateItems();

        return LiveResponse::make()
            ->fragment('list', $this->renderListHtml())
            ->dispatch('list:refreshed', null, ['count' => $this->refreshCount]);
    }

    #[LiveAction]
    public function addItem(): LiveResponse
    {
        $this->items[] = [
            'id' => uniqid(),
            'name' => 'New Item ' . rand(100, 999),
            'status' => 'pending',
            'price' => rand(10, 100) . '.' . rand(10, 99),
        ];

        return LiveResponse::make()->fragment('list', $this->renderListHtml());
    }

    private function renderListHtml(): string
    {
        $html = '<div class="space-y-2">';
        foreach ($this->items as $item) {
            $statusColor = match ($item['status']) {
                'completed' => 'bg-green-100 text-green-800',
                'processing' => 'bg-yellow-100 text-yellow-800',
                default => 'bg-gray-100 text-gray-800',
            };
            $html .= '<div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">';
            $html .= '<div>';
            $html .= '<div class="font-medium text-gray-900">' . htmlspecialchars($item['name']) . '</div>';
            $html .= '<div class="text-sm text-gray-500">$' . $item['price'] . '</div>';
            $html .= '</div>';
            $html .= '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $statusColor . '">' . ucfirst($item['status']) . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function render(): string|Element
    {
        return Container::make()
            ->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6')
            ->child(Text::h2('Live Component 分片更新（fragment）')->class('text-lg font-semibold mb-2'))
            ->child(Text::p('服务器按 fragment 名称返回局部 HTML，只更新组件内部已声明分片。')->textGray()->textSm()->class('mb-4'))
            ->child(
                Container::make()->flex('row')->gap(2)->class('mb-4')
                    ->child(
                        (new Element('button'))
                            ->liveAction('refreshList')
                            ->class('px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700')
                            ->text('刷新列表（随机生成）')
                    )
                    ->child(
                        (new Element('button'))
                            ->liveAction('addItem')
                            ->class('px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700')
                            ->text('添加一项')
                    )
            )
            ->child(
                Container::make()
                    ->liveFragment('list')
                    ->html($this->renderListHtml())
            )
            ->child(
                Container::make()
                    ->class('mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200')
                    ->state(['lastRefresh' => 0])
                    ->bindOn('list:refreshed', 'lastRefresh = $event.detail.count')
                    ->child(
                        Container::make()->class('flex items-center gap-2')
                            ->child(Text::p('刷新次数：')->class('text-sm text-gray-500'))
                            ->child((new Element('span'))->bindText('lastRefresh')->class('font-bold text-blue-600'))
                    )
            );
    }
}

class VirtualListDemo extends LiveComponent
{
    public array $items = [];
    public int $totalCount = 50;
    public int $page = 1;
    public int $perPage = 10;

    public function mount(): void
    {
        $this->loadItems();
    }

    private function loadItems(): void
    {
        $this->items = [];
        $names = ['Apple', 'Banana', 'Cherry', 'Durian', 'Elderberry', 'Fig', 'Grape', 'Honeydew', 'Kiwi', 'Lemon'];
        $statuses = ['pending', 'completed', 'processing'];
        $start = ($this->page - 1) * $this->perPage;

        for ($i = 0; $i < $this->perPage && ($start + $i) < $this->totalCount; $i++) {
            $idx = $start + $i;
            $this->items[] = [
                'id' => 'item-' . $idx,
                'index' => $idx,
                'name' => $names[array_rand($names)] . ' #' . ($idx + 1),
                'status' => $statuses[array_rand($statuses)],
                'price' => number_format(rand(100, 9999) / 100, 2),
            ];
        }
    }

    #[LiveAction]
    public function toggleStatus(string $itemId): LiveResponse
    {
        $foundIndex = null;
        foreach ($this->items as $index => &$item) {
            if ($item['id'] === $itemId) {
                $foundIndex = $index;
                $item['status'] = match ($item['status']) {
                    'pending' => 'processing',
                    'processing' => 'completed',
                    'completed' => 'pending',
                };
                break;
            }
        }

        if ($foundIndex !== null) {
            $itemHtml = $this->renderItemHtml($this->items[$foundIndex]);
            return LiveResponse::make()->fragment('item-' . $itemId, $itemHtml);
        }

        return LiveResponse::make();
    }

    #[LiveAction]
    public function deleteItem(string $itemId): LiveResponse
    {
        $this->items = array_values(array_filter($this->items, fn($item) => $item['id'] !== $itemId));
        $this->totalCount--;

        return LiveResponse::make()
            ->remove('#' . $itemId)
            ->dispatch('item:deleted', null, ['itemId' => $itemId]);
    }

    #[LiveAction]
    public function refreshList(): LiveResponse
    {
        $this->page = 1;
        $this->totalCount = 50;
        $this->loadItems();

        return LiveResponse::make()->fragment('list', $this->renderListHtml());
    }

    #[LiveAction]
    public function loadMore(): LiveResponse
    {
        $this->page++;
        $previousLastIndex = $this->items && $this->items !== [] ? end($this->items)['index'] : -1;
        $this->loadItems();

        $html = '';
        foreach ($this->items as $item) {
            if ($item['index'] > $previousLastIndex) {
                $html .= $this->renderWrappedItemHtml($item);
            }
        }

        return LiveResponse::make()->fragment('list', $html, 'append');
    }

    private function renderListHtml(): string
    {
        $html = '';
        foreach ($this->items as $item) {
            $html .= $this->renderWrappedItemHtml($item);
        }

        return $html;
    }

    private function renderWrappedItemHtml(array $item): string
    {
        return '<div id="' . $item['id'] . '" class="item-row" data-live-fragment="item-' . $item['id'] . '">' . $this->renderItemHtml($item) . '</div>';
    }

    private function renderItemHtml(array $item): string
    {
        $statusColor = match ($item['status']) {
            'completed' => 'bg-green-100 text-green-800 border-green-200',
            'processing' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            default => 'bg-gray-100 text-gray-800 border-gray-200',
        };
        $statusText = ucfirst($item['status']);

        $btnClass = match ($item['status']) {
            'completed' => 'bg-red-100 text-red-600 hover:bg-red-200',
            'processing' => 'bg-gray-100 text-gray-600 hover:bg-gray-200',
            default => 'bg-blue-100 text-blue-600 hover:bg-blue-200',
        };

        $borderColor = $item['borderColor'] ?? 'border-gray-200';
        $toggleParams = htmlspecialchars($this->encodeActionParams('toggleStatus', ['itemId' => $item['id']]), ENT_QUOTES, 'UTF-8');
        $deleteParams = htmlspecialchars($this->encodeActionParams('deleteItem', ['itemId' => $item['id']]), ENT_QUOTES, 'UTF-8');

        $html = '<div class="flex items-center justify-between p-3 bg-white rounded-lg border ' . $borderColor . ' hover:border-gray-300 transition-colors">';
        $html .= '<div class="flex items-center gap-3">';
        $html .= '<span class="text-sm font-medium text-gray-400 w-8">#' . $item['index'] . '</span>';
        $html .= '<div>';
        $html .= '<div class="font-medium text-gray-900">' . htmlspecialchars($item['name']) . '</div>';
        $html .= '<div class="text-sm text-gray-500">$' . $item['price'] . '</div>';
        $html .= '</div></div>';
        $html .= '<div class="flex items-center gap-2">';
        $html .= '<span class="px-2 py-1 text-xs font-medium rounded-full ' . $statusColor . '">' . $statusText . '</span>';
        $html .= '<button data-action="toggleStatus" data-action-params=\'' . $toggleParams . '\' class="px-2 py-1 text-xs rounded ' . $btnClass . ' transition-colors">切换</button>';
        $html .= '<button data-action="deleteItem" data-action-params=\'' . $deleteParams . '\' class="px-2 py-1 text-xs bg-red-100 text-red-600 rounded hover:bg-red-200 transition-colors">删除</button>';
        $html .= '</div></div>';

        return $html;
    }

    public function render(): string|Element
    {
        return Container::make()
            ->class('bg-white rounded-xl shadow-sm border border-gray-200 p-6')
            ->child(Text::h2('虚拟列表 - 分片更新（单条/列表）')->class('text-lg font-semibold mb-2'))
            ->child(Text::p('列表容器和每个条目都声明为 fragment，可按名字单独更新或追加。')->textGray()->textSm()->class('mb-4'))
            ->child(
                Container::make()
                    ->class('space-y-2 max-h-96 overflow-y-auto')
                    ->id('virtual-list-container')
                    ->liveFragment('list')
                    ->html($this->renderListHtml())
            )
            ->child(
                Container::make()->flex('row')->gap(2)->class('mt-4')
                    ->child(
                        (new Element('button'))
                            ->liveAction('loadMore')
                            ->class('px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700')
                            ->text('加载更多')
                    )
                    ->child(
                        (new Element('button'))
                            ->liveAction('refreshList')
                            ->class('px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300')
                            ->text('重置列表')
                    )
            )
            ->child(
                Container::make()
                    ->class('mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200')
                    ->state(['deletedCount' => 0])
                    ->bindOn('item:deleted', 'deletedCount++')
                    ->child(
                        Container::make()->class('flex items-center justify-between')
                            ->child(
                                Container::make()->class('flex items-center gap-2')
                                    ->child(Text::p('当前条目数：')->class('text-sm text-gray-500'))
                                    ->child((new Element('span'))->bindText('deletedCount')->class('font-bold text-blue-600'))
                            )
                            ->child(
                                Container::make()->class('flex items-center gap-2')
                                    ->child(Text::p('已删除：')->class('text-sm text-gray-500'))
                                    ->child((new Element('span'))->bindText('deletedCount')->class('font-bold text-red-600'))
                            )
                    )
            );
    }
}
