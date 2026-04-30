<?php

declare(strict_types=1);

namespace Admin\Pages;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Routing\Attribute\Route;
use Framework\Routing\Attribute\Get;
use Framework\Http\Response;
use Framework\View\Base\Element;
use Framework\View\Document\Document;
use Framework\View\Document\AssetRegistry;
use Framework\View\Container;
use Framework\View\Text;
use Framework\UX\UI\Button;
use Framework\UX\Dialog\Modal;
use Framework\UX\UI\Alert;
use Framework\UX\Menu\Dropdown;
use Framework\UX\Form\FormBuilder;
use Framework\UX\UI\Progress;
use Framework\UX\Layout\Row;
use Framework\UX\Layout\Grid;
use Framework\UX\UI\Badge;
use Framework\UX\Dialog\Drawer;
use Framework\UX\Form\SwitchField;
use Framework\UX\Form\SearchInput;
use Framework\UX\Form\RadioGroup;
use Framework\UX\UI\StatCard;
use Framework\UX\UI\Card;
use Framework\UX\UI\Tabs;
use Framework\UX\UI\Accordion;
use Framework\UX\UI\Breadcrumb;
use Framework\UX\UI\Avatar;
use Framework\UX\UI\Steps;
use Framework\UX\UI\Pagination;
use Framework\UX\UI\Skeleton;
use Framework\UX\Chart\Chart;

#[Route('/ux-demo')]
class UxDemoPage extends LiveComponent
{
    public int $count = 0;
    public string $name = 'John Doe';
    public string $email = 'john@example.com';
    public bool $showAlert = true;
    public int $currentPage = 1;
    public string $activeTab = 'tab-profile';

    public function index(): Response
    {
        Document::setTitle('UX 组件演示');
        AssetRegistry::getInstance()->ux();
        return Response::html($this->toHtml());
    }

    #[LiveAction]
    public function increment(): void
    {
        $this->count++;
        $this->toast("计数增加到 {$this->count}");
        $this->refresh('counter-box');
    }

    private function selectTab(string $tabsId, string $tabId): void
    {
        $this->ux('tabs', $tabsId, 'select', ['tabId' => $tabId]);
    }

    #[LiveAction]
    public function remoteSelectTab(): void
    {
        $this->activeTab = 'tab-security';
        $this->selectTab('main-tabs', 'tab-security');
        $this->toast('已从服务端遥控切换', 'info');
    }

    #[LiveAction]
    public function setPage(int $page): void
    {
        $this->currentPage = $page;
        $this->toast("跳转到第 {$page} 页");
    }

    #[LiveAction]
    public function toggleAlert(): void
    {
        $this->showAlert = !$this->showAlert;
    }

    public function render(): string|Element
    {
        return Container::main()->class('p-8 max-w-6xl mx-auto')->children(
            Text::h1('UX 组件系统')->class('mb-4'),
            Text::p('测试 UX 组件与 Live 系统的深度集成效果')->class('mb-8 text-gray-500'),

            $this->sectionStatCard(),
            $this->sectionLiveAction(),
            $this->sectionTabs(),
            $this->sectionModal(),
            $this->sectionDrawer(),
            $this->sectionDropdown(),
            $this->sectionAlert(),
            $this->sectionSteps(),
            $this->sectionPagination(),
            $this->sectionAvatar(),
            $this->sectionSkeleton(),
            $this->sectionChart(),

            Element::make('hr')->class('my-12'),
            Text::p(Text::small('UX 组件演示 - 驱动于框架核心 Live 系统'))->class('text-center text-gray-400')
        );
    }

    private function sectionStatCard(): Element
    {
        return Container::make()->class('mb-12')->children(
            Text::h2('1. 统计卡片 (StatCard)')->class('mb-4'),
            Grid::make()->cols(4)->gap(4)->children(
                StatCard::make()->title('总销售额')->value('￥128,430')->icon('💰')->trendUp('12.5%')->description('较上月'),
                StatCard::make()->title('新增用户')->value('1,240')->icon('👥')->trendUp('5.2%')->description('较昨日'),
                StatCard::make()->title('活跃订单')->value('45')->icon('📦')->trendDown('2.1%')->description('较上周'),
                StatCard::make()->title('系统负载')->value('18%')->icon('⚡')->description('运行正常')
            )
        );
    }

    private function sectionLiveAction(): Element
    {
        return Container::make()->class('mb-12')->children(
            Text::h2('2. Live Action 交互')->class('mb-4'),
            Text::p()->children(
                '当前计数: ',
                Text::strong((string)$this->count)
            )->liveFragment('counter-box'),
            Row::make()->class('mt-4')->gap(2)->children(
                Button::make()->label('➕ 增加')->primary()->liveAction('increment'),
                Button::make()->label('🔄 重置')->secondary()->liveAction('increment', 'click')
            )
        );
    }

    private function sectionTabs(): Element
    {
        return Container::make()->class('mb-12')->children(
            Text::h2('3. 选项卡 (Tabs)')->class('mb-4'),
            Row::make()->class('mb-4 gap-2')->children(
                Text::p('当前激活属性: ' . $this->activeTab)->class('flex-1'),
                Button::make()->label('服务端遥控切换到“安全设置”')->secondary()->sm()->liveAction('remoteSelectTab')
            ),
            Tabs::make()
                ->id('main-tabs')
                ->liveModel('activeTab')
                ->activeTab($this->activeTab)
                ->item('个人资料', '这是个人资料页的内容。', 'tab-profile')
                ->item('安全设置', '这是安全设置页的内容。', 'tab-security')
                ->item('通知管理', '这是通知管理页的内容。', 'tab-notify')
        );
    }

    private function sectionModal(): Element
    {
        $modal = Modal::make()
            ->id('demo-modal')
            ->title('系统确认')
            ->content('<p>这是一个可以通过属性、JS 或 Live 指令触发的模态框。</p>')
            ->footer(
                Button::make()->label('关闭')->secondary()->attr('data-ux-modal-close', 'demo-modal')
            );

        return Container::make()->class('mb-12')->children(
            Text::h2('4. 模态框 (Modal)')->class('mb-4'),
            Row::make()->gap(2)->children(
                $modal->trigger('打开模态框'),
                Button::make()->label('通过 $dispatch 打开')->secondary()->dispatch('ux:modal:open', "{ id: 'demo-modal' }")
            ),
            $modal
        );
    }

    private function sectionDrawer(): Element
    {
        $drawer = Drawer::make()
            ->id('demo-drawer')
            ->title('右侧抽屉')
            ->child('<p>内容区域...</p>');

        return Container::make()->class('mb-12')->children(
            Text::h2('5. 抽屉 (Drawer)')->class('mb-4'),
            $drawer->trigger('打开抽屉'),
            $drawer
        );
    }

    private function sectionDropdown(): Element
    {
        return Container::make()->class('mb-12')->children(
            Text::h2('6. 下拉菜单 (Dropdown)')->class('mb-4'),
            Dropdown::make()->label('更多操作')
                ->item('查看详情', '#')
                ->item('编辑项目', '#')
                ->divider()
                ->item('删除项目', '#', 'increment')
        );
    }

    private function sectionAlert(): Element
    {
        if (!$this->showAlert) return Container::make();

        return Container::make()->class('mb-12')->children(
            Text::h2('7. 警告框 (Alert)')->class('mb-4'),
            Alert::make()->title('重要提示')->message('这是一个集成状态的警告框')->warning()->dismissible()
        );
    }

    private function sectionSteps(): Element
    {
        return Container::make()->class('mb-12')->children(
            Text::h2('8. 步骤条 (Steps)')->class('mb-4'),
            Steps::make()->current(1)
                ->item('提交申请', '已于 2024-05-01 提交')
                ->item('部门审核', '正在审核中...')
                ->item('流程完成')
        );
    }

    private function sectionPagination(): Element
    {
        return Container::make()->class('mb-12')->children(
            Text::h2('9. 分页 (Pagination)')->class('mb-4'),
            Pagination::make()->total(100)->current($this->currentPage)->liveAction('setPage')
        );
    }

    private function sectionAvatar(): Element
    {
        return Container::make()->class('mb-12')->children(
            Text::h2('10. 头像 (Avatar)')->class('mb-4'),
            Row::make()->gap(4)->children(
                Avatar::make()->src('https://i.pravatar.cc/150?u=1')->status('online'),
                Avatar::make()->name('John Doe')->circle(),
                Avatar::make()->name('Jane Smith')->rounded(),
                Avatar::make()->size('lg')->status('busy')
            )
        );
    }

    private function sectionSkeleton(): Element
    {
        return Container::make()->class('mb-12')->children(
            Text::h2('11. 骨架屏 (Skeleton)')->class('mb-4'),
            Skeleton::make()->avatar(),
            Skeleton::make()->text()->count(2)->width('60%')
        );
    }

    private function sectionChart(): Element
    {
        return Container::make()->class('mb-12')->children(
            Text::h2('12. 图表 (Chart)')->class('mb-4'),
            Grid::make()->cols(2)->gap(4)->children(
                Chart::make()
                    ->id('sales-chart')
                    ->type('line')
                    ->title('销售趋势')
                    ->height(350)
                    ->labels(['1月', '2月', '3月', '4月', '5月', '6月'])
                    ->dataset('销售额', [12000, 19000, 15000, 18000, 22000, 25000], ['borderColor' => '#3b82f6', 'backgroundColor' => 'rgba(59, 130, 246, 0.1)']),
                Chart::make()
                    ->type('bar')
                    ->title('产品分布')
                    ->height(350)
                    ->labels(['产品A', '产品B', '产品C', '产品D'])
                    ->dataset('销量', [45, 78, 32, 65], ['backgroundColor' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']])
            )
        );
    }
}
