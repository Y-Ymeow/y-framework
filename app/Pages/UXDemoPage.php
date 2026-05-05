<?php

declare(strict_types=1);

namespace App\Pages;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\LiveComponent;
use Framework\Routing\Attribute\Route;
use Framework\UX\Display\Avatar;
use Framework\UX\Display\Badge;
use Framework\UX\Display\Card;
use Framework\UX\Display\Collapse;
use Framework\UX\Display\Divider;
use Framework\UX\Feedback\EmptyState;
use Framework\UX\Display\ListView;
use Framework\UX\Display\QRCode;
use Framework\UX\Display\StatCard;
use Framework\UX\Display\Tag;
use Framework\UX\Display\Timeline;
use Framework\UX\Feedback\Alert;
use Framework\UX\Feedback\Progress;
use Framework\UX\UI\Button;
use Framework\UX\Form\ColorPicker;
use Framework\UX\Form\DatePicker;
use Framework\UX\Form\DateRangePicker;
use Framework\UX\Form\Rate;
use Framework\UX\Form\Slider;
use Framework\UX\Form\TagInput;
use Framework\UX\Form\Transfer;
use Framework\UX\Form\TreeSelect;
use Framework\UX\Layout\Row;
use Framework\UX\Media\Carousel;
use Framework\UX\Media\Image;
use Framework\UX\Navigation\Steps;
use Framework\UX\Overlay\Popover;
use Framework\UX\Overlay\Tooltip;
use Framework\UX\Data\Calendar;
use Framework\UX\Dialog\Toast;
use Framework\UX\UI\Accordion;
use Framework\View\Base\Element;

#[Route('/demo/ux')]
class UXDemoPage extends LiveComponent
{
    public function render(): Element
    {
        $wrapper = Element::make('div')
            ->class('ux-demo')
            ->style('max-width: 1400px; margin: 0 auto; padding: 2rem;');

        $wrapper->child(
            Element::make('h1')
                ->style('font-size: 2rem; font-weight: 700; margin-bottom: 2rem; color: #111827;')
                ->text('UX 组件演示')
        );

        $wrapper->child($this->renderDisplaySection());
        $wrapper->child($this->renderFormSection());
        $wrapper->child($this->renderComplexSection());
        $wrapper->child($this->renderAction2());

        return $wrapper;
    }

    #[LiveAction()]
    public function showToast()
    {
        $this->toast('Hello');
    }


    protected function renderAction2(): Element
    {
        return Element::make('div')
            ->children(
                Button::make()
                    ->label("show Toast")
                    ->liveAction('showToast'),
                Toast::make(),
                // Accordion::make(),

            );
    }

    protected function renderDisplaySection(): Element
    {
        $section = Element::make('div')->style('margin-bottom: 3rem;');

        $section->child(
            Element::make('h2')
                ->style('font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; color: #374151;')
                ->text('展示组件')
        );

        $section->child($this->renderDemoCard('Divider 分割线', [
            Divider::make()->text('带文字的分割线'),
            Row::make()
                ->gap(2)
                ->child(Button::make()->label('按钮1')->sm())
                ->child(Divider::make()->vertical())
                ->child(Button::make()->label('按钮2')->sm()->secondary())
                ->child(Divider::make()->vertical()->dashed())
                ->child(Button::make()->label('按钮3')->sm()->primary()),
        ]));

        $section->child($this->renderDemoCard('Tag 标签', [
            Row::make()
                ->gap(2)
                ->child(Tag::make()->text('默认'))
                ->child(Tag::make()->text('主色')->primary())
                ->child(Tag::make()->text('成功')->success())
                ->child(Tag::make()->text('警告')->warning())
                ->child(Tag::make()->text('危险')->danger()),
            Row::make()
                ->gap(2)
                ->child(Tag::make()->text('小尺寸')->sm())
                ->child(Tag::make()->text('大尺寸')->lg())
                ->child(Tag::make()->text('可关闭')->closable())
                ->child(Tag::make()->text('带边框')->bordered()),
        ]));

        $section->child($this->renderDemoCard('Badge & Avatar', [
            Row::make()
                ->gap(4)
                ->child(Badge::make('新消息 5'))
                ->child(Badge::make()->text('热点')->dot()->danger())
                ->child(Avatar::make()->name('张三'))
                ->child(Avatar::make()->name('李四')->size('lg')),
        ]));

        $section->child($this->renderDemoCard('EmptyState 空状态', [
            EmptyState::make()
                ->description('暂无数据')
                ->extra(Button::make()->label('创建')),
        ]));

        $section->child($this->renderDemoCard('ListView 列表', [
            ListView::make()
                ->header('列表标题')
                ->item('列表项 1')
                ->item('列表项 2')
                ->item('列表项 3')
                ->bordered(),
        ]));

        $section->child($this->renderDemoCard('Timeline 时间轴', [
            Timeline::make()
                ->item('创建项目', '2024-01-01', null, 'blue')
                ->item('开发完成', '2024-02-15', null, 'green')
                ->item('测试阶段', '2024-03-01', null, 'yellow')
                ->item('正式上线', '2024-04-01', null, 'red'),
        ]));

        $section->child($this->renderDemoCard('Collapse 折叠面板', [
            Collapse::make()
                ->title('点击展开/折叠')
                ->icon('info-circle')
                ->child('这是折叠面板的内容区域，可以放置任何内容。'),
        ]));

        $section->child($this->renderDemoCard('StatCard 统计卡片', [
            Row::make()
                ->gap(4)
                ->child(StatCard::make()->title('总用户')->value('12,345')->trendUp('12%')->icon('<i class="bi bi-people"></i>'))
                ->child(StatCard::make()->title('收入')->value('¥89,234')->trendDown('5%')->icon('<i class="bi bi-currency-dollar"></i>')),
        ]));

        return $section;
    }

    protected function renderFormSection(): Element
    {
        $section = Element::make('div')->style('margin-bottom: 3rem;');

        $section->child(
            Element::make('h2')
                ->style('font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; color: #374151;')
                ->text('表单与交互组件')
        );

        $section->child($this->renderDemoCard('Rate 评分', [
            Row::make()
                ->gap(4)
                ->child(Rate::make()->value(3))
                ->child(Rate::make()->value(4.5)->allowHalf())
                ->child(Rate::make()->value(2)->disabled()),
        ]));

        $section->child($this->renderDemoCard('Slider 滑块', [
            Slider::make()->value(30),
            Slider::make()->rangeValue(20, 80)->range(),
        ]));

        $section->child($this->renderDemoCard('ColorPicker 颜色选择器', [
            Row::make()
                ->gap(4)
                ->child(ColorPicker::make()->value('#3b82f6'))
                ->child(ColorPicker::make()->value('#10b981')->allowClear()),
        ]));

        $section->child($this->renderDemoCard('DatePicker 日期选择器', [
            Row::make()
                ->gap(4)
                ->child(DatePicker::make()->placeholder('选择日期'))
                ->child(DateRangePicker::make()->placeholder('选择日期范围'))
                ->child(DatePicker::make()->placeholder('日期时间')->showTime()),
        ]));

        $section->child($this->renderDemoCard('TagInput 标签输入', [
            TagInput::make()
                ->value(['PHP', 'JavaScript', 'Vue'])
                ->placeholder('输入标签按回车'),
        ]));

        $section->child($this->renderDemoCard('Tooltip & Popover', [
            Row::make()
                ->gap(2)
                ->child(Tooltip::make()->content('上方提示')->top()->child(Button::make()->label('上')->sm()))
                ->child(Tooltip::make()->content('下方提示')->bottom()->child(Button::make()->label('下')->sm()))
                ->child(Tooltip::make()->content('左侧提示')->left()->child(Button::make()->label('左')->sm()))
                ->child(Tooltip::make()->content('右侧提示')->right()->child(Button::make()->label('右')->sm()))
                ->child(Popover::make()->title('标题')->content('气泡卡片内容')->child(Button::make()->label('Popover')->sm()->primary())),
        ]));

        $section->child($this->renderDemoCard('Alert & Progress', [
            Alert::make()->title('成功提示')->message('操作成功完成！')->success(),
            Progress::make()->value(75)->showLabel(),
            Progress::make()->value(45)->danger(),
        ]));

        $section->child($this->renderDemoCard('Steps 步骤条', [
            Steps::make()
                ->item('填写信息', 'finish')
                ->item('验证身份', 'process')
                ->item('完成注册', 'wait'),
        ]));

        return $section;
    }

    protected function renderComplexSection(): Element
    {
        $section = Element::make('div');

        $section->child(
            Element::make('h2')
                ->style('font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; color: #374151;')
                ->text('复杂组件')
        );

        $section->child($this->renderDemoCard('Transfer 穿梭框', [
            Transfer::make()
                ->titles('可选', '已选')
                ->showSearch()
                ->dataSource([
                    ['key' => '1', 'title' => '内容管理'],
                    ['key' => '2', 'title' => '用户管理'],
                    ['key' => '3', 'title' => '角色权限'],
                    ['key' => '4', 'title' => '系统设置'],
                    ['key' => '5', 'title' => '日志监控'],
                    ['key' => '6', 'title' => '数据分析'],
                ])
                ->targetKeys(['2', '5']),
        ]));

        $section->child($this->renderDemoCard('TreeSelect 树形选择', [
            TreeSelect::make()
                ->placeholder('请选择部门')
                ->showSearch()
                ->treeData([
                    [
                        'key' => '1',
                        'title' => '技术部',
                        'children' => [
                            ['key' => '1-1', 'title' => '前端组'],
                            ['key' => '1-2', 'title' => '后端组'],
                            ['key' => '1-3', 'title' => '测试组'],
                        ],
                    ],
                    [
                        'key' => '2',
                        'title' => '产品部',
                        'children' => [
                            ['key' => '2-1', 'title' => '产品设计'],
                            ['key' => '2-2', 'title' => '用户研究'],
                        ],
                    ],
                    ['key' => '3', 'title' => '运营部'],
                ]),
        ]));

        $section->child($this->renderDemoCard('Carousel 轮播图', [
            Carousel::make()
                ->item('<div style="background:linear-gradient(135deg,#3b82f6,#8b5cf6);height:200px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;font-weight:600;">幻灯片 1</div>')
                ->item('<div style="background:linear-gradient(135deg,#10b981,#06b6d4);height:200px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;font-weight:600;">幻灯片 2</div>')
                ->item('<div style="background:linear-gradient(135deg,#f59e0b,#ef4444);height:200px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;font-weight:600;">幻灯片 3</div>')
                ->autoplay()
                ->arrows(),
        ]));

        $section->child($this->renderDemoCard('QRCode 二维码', [
            Row::make()
                ->gap(4)
                ->child(QRCode::make()->value('https://example.com')->size(128))
                ->child(QRCode::make()->value('Hello World')->size(128)->color('#10b981')),
        ]));

        $section->child($this->renderDemoCard('Calendar 日历', [
            Calendar::make(),
        ]));

        return $section;
    }

    protected function renderDemoCard(string $title, array $components): Element
    {
        $card = Element::make('div')
            ->style('background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1.5rem;');

        $card->child(
            Element::make('h3')
                ->style('font-size: 1rem; font-weight: 600; margin-bottom: 1rem; color: #374151;')
                ->text($title)
        );

        foreach ($components as $component) {
            $card->child($component);
        }

        return $card;
    }
}
