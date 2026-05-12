<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class RichTextEditor extends UXComponent
{
    protected static ?string $componentName = 'rich-text-editor';

    protected ?string $content = null;
    protected string $placeholder = '';
    protected string $height = '400px';
    protected string $minHeight = '200px';
    protected bool $readonly = false;
    protected array $toolbar = [];

    private const DEFAULT_TOOLBAR = [
        ['undo', 'redo'],
        ['bold', 'italic', 'underline', 'strikethrough'],
        ['heading'],
        ['fontSize'],
        ['textColor'],
        ['alignLeft', 'alignCenter', 'alignRight', 'alignJustify'],
        ['unorderedList', 'orderedList'],
        ['blockquote', 'code'],
        ['link', 'image'],
        ['table', 'horizontalRule'],
        ['clearFormat'],
    ];

    private const BTN_META = [
        'bold'           => ['cmd' => 'bold',              'icon' => 'bold',           'title' => '加粗'],
        'italic'         => ['cmd' => 'italic',            'icon' => 'italic',         'title' => '斜体'],
        'underline'      => ['cmd' => 'underline',         'icon' => 'underline',      'title' => '下划线'],
        'strikethrough'  => ['cmd' => 'strikeThrough',     'icon' => 'strikethrough',  'title' => '删除线'],
        'undo'           => ['cmd' => 'undo',              'icon' => 'undo',           'title' => '撤销'],
        'redo'           => ['cmd' => 'redo',              'icon' => 'redo',           'title' => '重做'],
        'blockquote'     => ['cmd' => 'formatBlock',       'icon' => 'blockquote',     'title' => '引用',  'arg' => 'blockquote'],
        'code'           => ['cmd' => 'formatBlock',       'icon' => 'code',           'title' => '行内代码', 'arg' => 'pre'],
        'unorderedList'  => ['cmd' => 'insertUnorderedList','icon' => 'ulist',          'title' => '无序列表'],
        'orderedList'    => ['cmd' => 'insertOrderedList',  'icon' => 'olist',          'title' => '有序列表'],
        'horizontalRule' => ['cmd' => 'insertHorizontalRule','icon' => 'hr',            'title' => '分割线'],
        'clearFormat'    => ['cmd' => 'removeFormat',       'icon' => 'clear',          'title' => '清除格式'],
        'alignLeft'      => ['cmd' => 'justifyLeft',        'icon' => 'align-left',     'title' => '左对齐'],
        'alignCenter'    => ['cmd' => 'justifyCenter',      'icon' => 'align-center',   'title' => '居中'],
        'alignRight'     => ['cmd' => 'justifyRight',       'icon' => 'align-right',    'title' => '右对齐'],
        'alignJustify'   => ['cmd' => 'justifyFull',        'icon' => 'align-justify',  'title' => '两端对齐'],
        'heading'        => ['type' => 'heading'],
        'fontSize'       => ['type' => 'fontSize'],
        'textColor'      => ['type' => 'textColor'],
        'link'           => ['type' => 'popup', 'popup' => 'link'],
        'image'          => ['type' => 'popup', 'popup' => 'image'],
        'table'          => ['type' => 'popup', 'popup' => 'table'],
    ];

    private const ICONS = [
        'bold'          => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>',
        'italic'        => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4h-8z"/></svg>',
        'underline'     => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>',
        'strikethrough' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M6.85 7.08C6.85 4.37 9.45 3 12.24 3c1.64 0 3 .49 3.9 1.28.77.65 1.46 1.73 1.46 3.24h-3.01c0-.31-.05-.59-.15-.85-.29-.86-1.2-1.28-2.25-1.28-1.86 0-2.34 1.02-2.34 1.7 0 .48.25.88.74 1.21.38.25.77.48 1.41.7H7.39c-.21-.34-.54-.89-.54-1.92zM21 12v-2H3v2h9.62c1.15.45 1.96.75 1.96 1.97 0 1-.81 1.67-2.28 1.67-1.54 0-2.93-.54-2.93-2.51H6.4c0 2.87 2.27 4.87 5.58 4.87 2.28 0 4.28-.84 5.12-2.22.63-1.04.8-2.03.8-2.95h3.1c0 1.52-.37 2.92-1.04 4.06-.93 1.56-2.57 2.61-5.1 2.61-2.58 0-4.4-.93-5.37-2.33C8.56 17.11 8 15.61 8 13.97H5v-2h16z"/></svg>',
        'undo'          => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>',
        'redo'          => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></svg>',
        'blockquote'    => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M6 17h3l2-4V7H5v6h3l-2 4zm8 0h3l2-4V7h-6v6h3l-2 4z"/></svg>',
        'code'          => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>',
        'ulist'         => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.68-1.5 1.5s.68 1.5 1.5 1.5 1.5-.68 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/></svg>',
        'olist'         => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V5H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/></svg>',
        'hr'            => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M6 11h12v2H6z"/></svg>',
        'clear'         => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M3.27 5L2 6.27l6.97 6.98L6.5 19h3l1.57-3.66L16.73 21 18 19.73 3.55 5.27 3.27 5zM6 5v.18L8.82 8h2.4l-.72 1.68 2.1 2.1L14.21 8H20V5H6z"/></svg>',
        'align-left'    => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M15 15H3v2h12v-2zm0-8H3v2h12V7zM3 13h18v-2H3v2zm0 8h18v-2H3v2zM3 3v2h18V3H3z"/></svg>',
        'align-center'  => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M7 15v2h10v-2H7zm-4 6h18v-2H3v2zm0-8h18v-2H3v2zm4-6v2h10V7H7zM3 3v2h18V3H3z"/></svg>',
        'align-right'   => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M3 21h18v-2H3v2zm6-4h12v-2H9v2zm-6-4h18v-2H3v2zm6-4h12V7H9v2zM3 3v2h18V3H3z"/></svg>',
        'align-justify' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M3 21h18v-2H3v2zm0-4h18v-2H3v2zm0-4h18v-2H3v2zm0-4h18V7H3v2zm0-6v2h18V3H3z"/></svg>',
        'link'          => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>',
        'image'         => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>',
        'table'         => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M4 21h15.89c.59 0 1.07-.52 1.07-1.11V20H4v1zm17-5H3v-1h18v1zm0-4H3v-1h18v1zm0-4H3V7h18v1zm0-4H3c-.55 0-1 .45-1 1v1h20V5c0-.55-.45-1-1-1z"/></svg>',
        'color'         => '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>',
        'popup-close'   => '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
    ];

    private const COLORS = [
        '#000000', '#e60000', '#ff9900', '#ffff00', '#008a00',
        '#0066cc', '#9933ff', '#ffffff', '#facccc', '#ffebcc',
        '#ffffcc', '#cce8cc', '#cce0ff', '#ebccff', '#bbbbbb',
        '#f06666', '#ffc266', '#ffff66', '#66b966', '#66a3e0',
        '#c285ff', '#888888', '#a10000', '#b26b00', '#b2b200',
        '#006100', '#0047b2', '#6b24b2', '#444444', '#5c0000',
        '#663d00', '#666600', '#003700', '#002966', '#3d1466',
    ];

    protected function init(): void
    {
        if (empty($this->toolbar)) {
            $this->toolbar = self::DEFAULT_TOOLBAR;
        }
        $this->registerCss($this->css());
        $this->registerJs('rich-text-editor', $this->js());
    }

    public function toolbar(array $groups): static
    {
        $this->toolbar = $groups;
        return $this;
    }

    public function plugins(string ...$names): static
    {
        $this->toolbar = [$names];
        return $this;
    }

    public function content(string $html): static
    {
        $this->content = $html;
        return $this;
    }

    public function placeholder(string $text): static
    {
        $this->placeholder = $text;
        return $this;
    }

    public function height(string $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function minHeight(string $minHeight): static
    {
        $this->minHeight = $minHeight;
        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;
        return $this;
    }

    // ─── 构建 Element ─────────────────────────────────────

    protected function toElement(): Element
    {
        $el = Element::make('div');
        $this->buildElement($el);
        $el->class('ux-rich-text-editor');

        if ($this->readonly) {
            $el->class('ux-rich-text-editor-readonly');
        }

        $el->data('editor-min-height', $this->minHeight);
        if ($this->placeholder) {
            $el->data('editor-placeholder', $this->placeholder);
        }
        if ($this->readonly) {
            $el->data('editor-readonly', 'true');
        }
        if ($this->uxModel) {
            $el->data('editor-model', $this->uxModel);
        }

        $el->child($this->buildToolbar());
        $el->child($this->buildContentArea());

        // 居中 Modal Popups（由 JS 控制显示/隐藏）
        $el->child($this->buildPopup('link', '插入链接', self::ICONS['link'], [
            ['url', 'url', '链接地址', 'text'],
            ['text', 'text', '显示文本', 'text'],
            ['target', 'target', '', 'select'],
        ]));
        $el->child($this->buildImagePopup());
        $el->child($this->buildPopup('table', '插入表格', self::ICONS['table'], [
            ['rows', 'rows', '行数', 'number', '3'],
            ['cols', 'cols', '列数', 'number', '3'],
        ]));

        if ($this->uxModel) {
            $input = $this->createLiveModelInput($this->content ?? '');
            if ($input) {
                $el->child($input);
            }
        }

        return $el;
    }

    private function buildToolbar(): Element
    {
        $toolbar = Element::make('div')->class('ux-editor-toolbar');

        foreach ($this->toolbar as $i => $group) {
            if ($i > 0) {
                $toolbar->child(Element::make('span')->class('ux-editor-sep'));
            }
            $grp = Element::make('span')->class('ux-editor-tb-group');
            foreach ((array)$group as $name) {
                $meta = self::BTN_META[$name] ?? null;
                if (!$meta) continue;

                $type = $meta['type'] ?? 'btn';
                match ($type) {
                    'heading'   => $grp->child($this->buildHeadingSelect()),
                    'fontSize'  => $grp->child($this->buildFontSizeSelect()),
                    'textColor' => $grp->child($this->buildColorPicker()),
                    'popup'     => $grp->child($this->buildBtn($name, self::ICONS[$name], $meta['title'] ?? '')),
                    default     => $grp->child($this->buildBtn($name, self::ICONS[$meta['icon']], $meta['title'])),
                };
            }
            $toolbar->child($grp);
        }

        return $toolbar;
    }

    private function buildPopup(string $name, string $title, string $icon, array $fields): Element
    {
        $backdrop = Element::make('div')
            ->class('ux-editor-modal-backdrop')
            ->attr('data-editor-modal-backdrop', $name)
            ->style('display:none');

        $popup = Element::make('div')
            ->class('ux-editor-modal')
            ->attr('data-editor-modal', $name);

        $popup->child(
            Element::make('div')->class('ux-editor-modal-header')
                ->html($icon)
                ->child(Element::make('span')->text($title))
                ->child(
                    Element::make('button')
                        ->attr('type', 'button')
                        ->class('ux-editor-modal-close')
                        ->attr('data-editor-action', 'popup-close')
                        ->html(self::ICONS['popup-close'])
                )
        );

        $body = Element::make('div')->class('ux-editor-modal-body');
        foreach ($fields as $f) {
            if ($f[0] === 'target') {
                $sel = Element::make('select')
                    ->attr('name', 'target')
                    ->class('ux-editor-modal-input');
                $sel->child(Element::make('option')->attr('value', '_self')->text('当前窗口'));
                $sel->child(Element::make('option')->attr('value', '_blank')->text('新窗口'));
                $body->child($sel);
                continue;
            }
            $field = Element::make('input')
                ->attr('type', $f[3] ?? 'text')
                ->attr('name', $f[0])
                ->attr('placeholder', $f[2])
                ->class('ux-editor-modal-input');
            if ($f[3] === 'number') {
                $field->attr('min', '1')->attr('max', '20');
            }
            if (isset($f[4])) {
                $field->attr('value', $f[4]);
            }
            $body->child($field);
        }

        $popup->child($body);

        $btns = Element::make('div')->class('ux-editor-modal-btns');
        $btns->child(
            Element::make('button')
                ->attr('type', 'button')
                ->class('ux-editor-modal-btn', 'ux-editor-modal-cancel')
                ->attr('data-editor-action', 'popup-close')
                ->text('取消')
        );
        $btns->child(
            Element::make('button')
                ->attr('type', 'button')
                ->class('ux-editor-modal-btn', 'ux-editor-modal-confirm')
                ->attr('data-editor-action', $name . '-confirm')
                ->text('确认')
        );
        $popup->child($btns);

        $backdrop->child($popup);
        return $backdrop;
    }

    private function buildImagePopup(): Element
    {
        $name = 'image';
        $backdrop = Element::make('div')
            ->class('ux-editor-modal-backdrop')
            ->attr('data-editor-modal-backdrop', $name)
            ->style('display:none');

        $popup = Element::make('div')
            ->class('ux-editor-modal')
            ->attr('data-editor-modal', $name);

        $popup->child(
            Element::make('div')->class('ux-editor-modal-header')
                ->html(self::ICONS['image'])
                ->child(Element::make('span')->text('插入图片'))
                ->child(
                    Element::make('button')
                        ->attr('type', 'button')
                        ->class('ux-editor-modal-close')
                        ->attr('data-editor-action', 'popup-close')
                        ->html(self::ICONS['popup-close'])
                )
        );

        $tabs = Element::make('div')->class('ux-editor-modal-tabs');
        $tabs->child(
            Element::make('button')
                ->attr('type', 'button')
                ->class('ux-editor-modal-tab', 'active')
                ->attr('data-editor-tab', 'image-url')
                ->text('链接地址')
        );
        $tabs->child(
            Element::make('button')
                ->attr('type', 'button')
                ->class('ux-editor-modal-tab')
                ->attr('data-editor-tab', 'image-media')
                ->text('媒体库')
        );
        $popup->child($tabs);

        $body = Element::make('div')->class('ux-editor-modal-body');

        $urlPanel = Element::make('div')
            ->class('ux-editor-modal-tab-panel', 'active')
            ->attr('data-editor-panel', 'image-url');
        $urlPanel->child(
            Element::make('input')
                ->attr('type', 'text')->attr('name', 'url')
                ->attr('placeholder', '图片地址')
                ->class('ux-editor-modal-input')
        );
        $urlPanel->child(
            Element::make('input')
                ->attr('type', 'text')->attr('name', 'alt')
                ->attr('placeholder', '替代文本')
                ->class('ux-editor-modal-input')
        );
        $body->child($urlPanel);

        $mediaPanel = Element::make('div')
            ->class('ux-editor-modal-tab-panel')
            ->attr('data-editor-panel', 'image-media');
        $mediaPanel->child(
            Element::make('div')->class('ux-editor-media-search')
                ->child(
                    Element::make('input')
                        ->attr('type', 'text')
                        ->attr('name', 'media-search')
                        ->attr('placeholder', '搜索媒体文件...')
                        ->class('ux-editor-modal-input')
                )
        );
        $mediaPanel->child(
            Element::make('div')->class('ux-editor-media-loading')
                ->style('display:none')
                ->text('加载中...')
        );
        $mediaPanel->child(
            Element::make('div')->class('ux-editor-media-grid')
                ->attr('data-editor-media-grid', '')
        );
        $body->child($mediaPanel);

        $popup->child($body);

        $btns = Element::make('div')->class('ux-editor-modal-btns');
        $btns->child(
            Element::make('button')
                ->attr('type', 'button')
                ->class('ux-editor-modal-btn', 'ux-editor-modal-cancel')
                ->attr('data-editor-action', 'popup-close')
                ->text('取消')
        );
        $btns->child(
            Element::make('button')
                ->attr('type', 'button')
                ->class('ux-editor-modal-btn', 'ux-editor-modal-confirm')
                ->attr('data-editor-action', 'image-confirm')
                ->text('插入图片')
        );
        $popup->child($btns);

        $backdrop->child($popup);
        return $backdrop;
    }

    private function buildBtn(string $action, string $icon, string $title): Element
    {
        return Element::make('button')
            ->attr('type', 'button')
            ->class('ux-editor-btn')
            ->attr('data-editor-action', $action)
            ->attr('title', $title)
            ->html($icon);
    }

    private function buildHeadingSelect(): Element
    {
        return Element::make('select')
            ->class('ux-editor-select')
            ->attr('data-editor-action', 'heading')
            ->children(
                Element::make('option')->attr('value', '')->text('正文'),
                Element::make('option')->attr('value', 'h1')->text('标题 1'),
                Element::make('option')->attr('value', 'h2')->text('标题 2'),
                Element::make('option')->attr('value', 'h3')->text('标题 3'),
                Element::make('option')->attr('value', 'h4')->text('标题 4'),
            );
    }

    private function buildFontSizeSelect(): Element
    {
        return Element::make('select')
            ->class('ux-editor-select')
            ->attr('data-editor-action', 'fontSize')
            ->children(
                Element::make('option')->attr('value', '')->text('字号'),
                Element::make('option')->attr('value', '1')->text('极小'),
                Element::make('option')->attr('value', '2')->text('较小'),
                Element::make('option')->attr('value', '3')->text('正常'),
                Element::make('option')->attr('value', '4')->text('较大'),
                Element::make('option')->attr('value', '5')->text('大'),
                Element::make('option')->attr('value', '6')->text('很大'),
                Element::make('option')->attr('value', '7')->text('极大'),
            );
    }

    private function buildColorPicker(): Element
    {
        $wrap = Element::make('span')->class('ux-editor-color-wrap');

        $wrap->child(
            Element::make('button')
                ->attr('type', 'button')
                ->class('ux-editor-btn')
                ->attr('data-editor-action', 'textColor')
                ->attr('title', '文字颜色')
                ->html(self::ICONS['color'])
        );

        $picker = Element::make('span')->class('ux-editor-color-picker');
        foreach (self::COLORS as $color) {
            $picker->child(
                Element::make('button')
                    ->attr('type', 'button')
                    ->class('ux-editor-color-swatch')
                    ->attr('data-color', $color)
                    ->style("background:{$color}")
                    ->attr('title', $color)
            );
        }
        $picker->child(
            Element::make('input')
                ->attr('type', 'color')
                ->class('ux-editor-color-input')
                ->attr('value', '#000000')
        );
        $wrap->child($picker);
        return $wrap;
    }

    private function buildContentArea(): Element
    {
        $area = Element::make('div')
            ->class('ux-editor-content')
            ->attr('spellcheck', 'true')
            ->style("min-height:{$this->minHeight};height:{$this->height};");

        if ($this->readonly) {
            $area->attr('contenteditable', 'false');
        } else {
            $area->attr('contenteditable', 'true');
        }

        if ($this->content !== null) {
            $area->html($this->content);
        } elseif ($this->placeholder) {
            $area->html('');
        }

        return $area;
    }

    // ─── JS ───────────────────────────────────────────────

    private function js(): string
    {
        return <<<'JS'
        const CMD = {
            bold:['bold',null], italic:['italic',null], underline:['underline',null],
            strikethrough:['strikeThrough',null], undo:['undo',null], redo:['redo',null],
            blockquote:['formatBlock','blockquote'], code:['formatBlock','pre'],
            unorderedList:['insertUnorderedList',null], orderedList:['insertOrderedList',null],
            horizontalRule:['insertHorizontalRule',null], clearFormat:['removeFormat',null],
            alignLeft:['justifyLeft',null], alignCenter:['justifyCenter',null],
            alignRight:['justifyRight',null], alignJustify:['justifyFull',null],
        };

        const RTE = {
            _currentPopup: null,

            init() {
                document.querySelectorAll('.ux-rich-text-editor').forEach(el => this._init(el));
                new MutationObserver(ms => {
                    for (const m of ms) for (const n of m.addedNodes) {
                        if (n.nodeType !== 1) continue;
                        if (n.classList?.contains('ux-rich-text-editor')) this._init(n);
                        n.querySelectorAll?.('.ux-rich-text-editor').forEach(c => this._init(c));
                    }
                }).observe(document.body, { childList: true, subtree: true });
            },

            _init(el) {
                if (el._uxRteInit) return;
                el._uxRteInit = true;
                const tb = el.querySelector('.ux-editor-toolbar');
                const ed = el.querySelector('.ux-editor-content');
                if (!ed) return;
                const ph = el.dataset.editorPlaceholder || '';
                const ro = el.dataset.editorReadonly === 'true';
                ed.style.minHeight = el.dataset.editorMinHeight || '200px';

                if (ro) { ed.contentEditable = 'false'; if (tb) tb.style.display = 'none'; return; }
                ed.contentEditable = 'true';
                if (ph && !ed.textContent.trim()) ed.dataset.placeholder = ph;

                el.addEventListener('click', e => {
                    const b = e.target.closest('[data-editor-action]');
                    if (!b) return; e.preventDefault();
                    this._exec(b.dataset.editorAction, b, ed, el);
                });
                el.addEventListener('change', e => {
                    const s = e.target.closest('[data-editor-action]');
                    if (!s) return;
                    this._exec(s.dataset.editorAction, s, ed, el);
                });
                el.addEventListener('click', e => {
                    const c = e.target.closest('.ux-editor-color-swatch');
                    if (c) { e.preventDefault(); document.execCommand('foreColor', false, c.dataset.color); ed.focus(); this._emit(el); }
                });
                el.addEventListener('input', e => {
                    const ci = e.target.closest('.ux-editor-color-input');
                    if (ci) { document.execCommand('foreColor', false, ci.value); ed.focus(); this._emit(el); }
                });
                el.addEventListener('keydown', e => {
                    if (e.key === 'Enter') {
                        const cf = e.target.closest('[data-editor-action$="-confirm"]');
                        if (cf) { e.preventDefault(); this._exec(cf.dataset.editorAction, cf, ed, el); }
                    }
                    if (e.key === 'Escape') {
                        const pp = e.target.closest('.ux-editor-modal-backdrop');
                        if (pp) { pp.style.display = 'none'; this._currentPopup = null; }
                    }
                });
                el.addEventListener('keydown', e => {
                    if (e.key === 'Enter' && e.target.closest('.ux-editor-modal-input')) {
                        const popup = e.target.closest('.ux-editor-modal-backdrop');
                        if (popup) {
                            const name = popup.querySelector('[data-editor-modal]')?.dataset.editorModal;
                            const cf = popup.querySelector('[data-editor-action="' + name + '-confirm"]');
                            if (cf) { e.preventDefault(); cf.click(); }
                        }
                    }
                });
                el.addEventListener('click', e => {
                    const tab = e.target.closest('[data-editor-tab]');
                    if (tab) { e.preventDefault(); this._switchTab(tab.dataset.editorTab, el); return; }
                    const mediaItem = e.target.closest('[data-editor-media-item]');
                    if (mediaItem) {
                        e.preventDefault();
                        const url = mediaItem.dataset.editorMediaUrl;
                        const popup = el.querySelector('[data-editor-modal-backdrop="image"]');
                        if (popup) {
                            const urlInput = popup.querySelector('[name="url"]');
                            if (urlInput) urlInput.value = url;
                            this._switchTab('image-url', el);
                        }
                        return;
                    }
                });
                el.addEventListener('input', e => {
                    const search = e.target.closest('[name="media-search"]');
                    if (search) {
                        clearTimeout(el._mediaSearchTimer);
                        el._mediaSearchTimer = setTimeout(() => this._fetchMedia(el, search.value), 300);
                    }
                });

                ed.addEventListener('input', () => {
                    this._emit(el);
                    ed.dataset.placeholder = ed.textContent.trim() ? '' : ph;
                });
                ed.addEventListener('keydown', e => {
                    if (e.key === 'Tab') { e.preventDefault(); document.execCommand('insertHTML', false, '&#009;'); return; }
                    if (e.key === 'Enter') { this._onEnter(e, ed, el); return; }
                    if (e.key === ' ') { this._onSpace(e, ed); }
                });
                ed.addEventListener('paste', e => {
                    e.preventDefault();
                    const h = (e.clipboardData || window.clipboardData).getData('text/html');
                    const t = (e.clipboardData || window.clipboardData).getData('text/plain');
                    if (h) document.execCommand('insertHTML', false, this._clean(h));
                    else document.execCommand('insertText', false, t);
                });
            },

            _exec(name, el, ed, root) {
                if (name === 'heading' || name === 'fontSize') {
                    const v = el.value; if (!v) return;
                    document.execCommand(name === 'heading' ? 'formatBlock' : 'fontSize', false, v);
                    ed.focus(); this._emit(root); return;
                }

                if (name === 'link' || name === 'image' || name === 'table') {
                    this._showPopup(name, ed, root);
                    return;
                }

                if (name === 'popup-close') {
                    const pp = el.closest('.ux-editor-modal-backdrop');
                    if (pp) { pp.style.display = 'none'; this._currentPopup = null; }
                    return;
                }

                if (name === 'link-confirm') {
                    this._hidePopup();
                    const popup = el.closest('.ux-editor-modal-backdrop');
                    if (!popup) return;
                    const url = (popup.querySelector('[name="url"]')?.value || '').trim();
                    const text = (popup.querySelector('[name="text"]')?.value || '').trim();
                    const target = popup.querySelector('[name="target"]')?.value || '_blank';
                    if (!url) return;
                    ed.focus();
                    const sel = window.getSelection();
                    let rng = sel.rangeCount ? sel.getRangeAt(0) : null;
                    let existingA = null;
                    if (rng) {
                        let n = rng.startContainer; if (n.nodeType === 3) n = n.parentNode;
                        existingA = n.closest('a');
                    }
                    if (existingA) {
                        existingA.href = url; existingA.target = target; existingA.rel = 'noopener';
                        if (text && text !== existingA.textContent) existingA.textContent = text;
                    } else {
                        const txt = text || url;
                        if (rng && rng.toString()) {
                            rng.deleteContents();
                            const a = document.createElement('a');
                            a.href = url; a.target = target; a.rel = 'noopener'; a.textContent = txt;
                            rng.insertNode(a);
                        } else {
                            const a = document.createElement('a');
                            a.href = url; a.target = target; a.rel = 'noopener'; a.textContent = txt;
                            sel.removeAllRanges();
                            const r = document.createRange();
                            r.deleteContents();
                            r.insertNode(a);
                            r.setStartAfter(a); r.collapse(true);
                            sel.addRange(r);
                        }
                    }
                    this._emit(root); return;
                }

                if (name === 'image-confirm') {
                    this._hidePopup();
                    const popup = el.closest('.ux-editor-modal-backdrop');
                    if (!popup) return;
                    const u = (popup.querySelector('[name="url"]')?.value || '').trim();
                    const alt = (popup.querySelector('[name="alt"]')?.value || '').trim();
                    if (!u) return;
                    ed.focus();
                    document.execCommand('insertImage', false, u);
                    setTimeout(() => {
                        const imgs = ed.querySelectorAll('img');
                        const li = imgs[imgs.length - 1];
                        if (li) { li.style.maxWidth = '100%'; li.alt = alt; li.classList.add('ux-editor-image'); }
                        this._emit(root);
                    }, 10);
                    return;
                }

                if (name === 'table-confirm') {
                    this._hidePopup();
                    const popup = el.closest('.ux-editor-modal-backdrop');
                    if (!popup) return;
                    const rs = parseInt(popup.querySelector('[name="rows"]')?.value || '3');
                    const cs = parseInt(popup.querySelector('[name="cols"]')?.value || '3');
                    if (!rs || !cs) return;
                    let h = '<table class="ux-editor-table"><tbody>';
                    for (let r = 0; r < rs; r++) {
                        h += '<tr>';
                        for (let c = 0; c < cs; c++) h += '<td style="border:1px solid #d1d5db;padding:8px 12px;min-width:60px"><p><br></p></td>';
                        h += '</tr>';
                    }
                    h += '</tbody></table><p><br></p>';
                    ed.focus(); document.execCommand('insertHTML', false, h); this._emit(root); return;
                }

                const c = CMD[name]; if (c) { document.execCommand(c[0], false, c[1]); ed.focus(); this._emit(root); }
            },

            _showPopup(name, ed, root) {
                if (this._currentPopup) this._currentPopup.style.display = 'none';
                this._currentPopup = root.querySelector('[data-editor-modal-backdrop="' + name + '"]');
                if (!this._currentPopup) return;

                if (name === 'link') {
                    const sel = window.getSelection();
                    if (sel.rangeCount) {
                        const r = sel.getRangeAt(0);
                        let n = r.startContainer; if (n.nodeType === 3) n = n.parentNode;
                        const a = n.closest('a');
                        const urlInp = this._currentPopup.querySelector('[name="url"]');
                        const txtInp = this._currentPopup.querySelector('[name="text"]');
                        const tgtSel = this._currentPopup.querySelector('[name="target"]');
                        if (a) {
                            if (urlInp) urlInp.value = a.getAttribute('href') || '';
                            if (txtInp) txtInp.value = a.textContent || '';
                            if (tgtSel) tgtSel.value = a.getAttribute('target') || '_self';
                        } else {
                            if (urlInp) urlInp.value = '';
                            if (txtInp) txtInp.value = r?.toString() || '';
                            if (tgtSel) tgtSel.value = '_blank';
                        }
                    }
                }

                if (name === 'image') {
                    this._switchTab('image-url', root);
                    const urlInput = this._currentPopup.querySelector('[name="url"]');
                    if (urlInput) urlInput.value = '';
                    const altInput = this._currentPopup.querySelector('[name="alt"]');
                    if (altInput) altInput.value = '';
                    const searchInput = this._currentPopup.querySelector('[name="media-search"]');
                    if (searchInput) searchInput.value = '';
                }

                this._currentPopup.style.display = 'flex';
                const firstInput = this._currentPopup.querySelector('input');
                if (firstInput) firstInput.focus();
            },

            _hidePopup() {
                if (this._currentPopup) { this._currentPopup.style.display = 'none'; this._currentPopup = null; }
            },

            _switchTab(tabName, el) {
                const popup = el.querySelector('[data-editor-modal-backdrop="image"]');
                if (!popup) return;
                popup.querySelectorAll('[data-editor-tab]').forEach(t => t.classList.toggle('active', t.dataset.editorTab === tabName));
                popup.querySelectorAll('[data-editor-panel]').forEach(p => p.classList.toggle('active', p.dataset.editorPanel === tabName));
                if (tabName === 'image-media') {
                    const grid = popup.querySelector('[data-editor-media-grid]');
                    if (grid && !grid.children.length) this._fetchMedia(el, '');
                }
            },

            _fetchMedia(el, search) {
                const popup = el.querySelector('[data-editor-modal-backdrop="image"]');
                if (!popup) return;
                const grid = popup.querySelector('[data-editor-media-grid]');
                const loading = popup.querySelector('.ux-editor-media-loading');
                if (!grid) return;
                loading.style.display = 'block';
                grid.innerHTML = '';
                const params = new URLSearchParams({ filter: 'image', per_page: '40' });
                if (search) params.set('search', search);
                fetch('/api/media?' + params.toString())
                    .then(r => r.json())
                    .then(data => {
                        loading.style.display = 'none';
                        const items = data?.data || [];
                        if (!items.length) { grid.innerHTML = '<div class="ux-editor-media-empty">暂无图片</div>'; return; }
                        items.forEach(item => {
                            const mime = item.mime_type || '';
                            const isImage = mime.startsWith('image/');
                            const url = '/media/' + (item.path || '');
                            const div = document.createElement('div');
                            div.className = 'ux-editor-media-item';
                            div.setAttribute('data-editor-media-item', '');
                            div.setAttribute('data-editor-media-url', url);
                            if (isImage) {
                                const img = document.createElement('img');
                                img.src = url + '?w=150&h=150&fit=true';
                                img.alt = item.alt || '';
                                img.loading = 'lazy';
                                div.appendChild(img);
                            } else {
                                const ext = (item.extension || 'FILE').toUpperCase();
                                div.innerHTML = '<span class="ux-editor-media-item-icon">' + ext + '</span>';
                            }
                            grid.appendChild(div);
                        });
                    })
                    .catch(() => {
                        loading.style.display = 'none';
                        grid.innerHTML = '<div class="ux-editor-media-empty">加载失败</div>';
                    });
            },

            _emit(el) {
                const ed = el.querySelector('.ux-editor-content');
                const inp = el.querySelector('.ux-live-bridge-input');
                if (inp) { inp.value = ed?.innerHTML || ''; inp.dispatchEvent(new Event('input', { bubbles: true })); }
            },

            _onEnter(e, ed, root) {
                const sel = window.getSelection();
                if (!sel.rangeCount) return;
                const r = sel.getRangeAt(0);
                const block = this._blockAt(r.startContainer, ed);
                if (!block) return;
                const tag = block.tagName.toLowerCase();
                const empty = !block.textContent.trim() || block.innerHTML === '<br>' || block.innerHTML === '';

                if (/^h[1-6]$/.test(tag)) {
                    e.preventDefault();
                    if (this._atEnd(r, block)) {
                        this._insertAfter(block, '<p><br></p>', ed, root);
                    } else {
                        const after = r.startOffset < (r.startContainer.textContent?.length || 0);
                        document.execCommand('insertParagraph');
                        if (!after) {
                            setTimeout(() => {
                                const nb = this._blockAt(sel.anchorNode, ed);
                                if (nb && /^h[1-6]$/.test(nb.tagName.toLowerCase())) this._unwrapBlock(nb);
                            }, 0);
                        }
                    }
                    return;
                }

                if (empty) {
                    if (tag === 'blockquote') {
                        e.preventDefault();
                        this._insertAfter(block.closest('blockquote') || block, '<p><br></p>', ed, root);
                        return;
                    }
                    if (tag === 'pre') {
                        e.preventDefault();
                        this._insertAfter(block, '<p><br></p>', ed, root);
                        return;
                    }
                    if (tag === 'li') {
                        e.preventDefault();
                        document.execCommand('outdent');
                        return;
                    }
                }
            },

            _onSpace(e, ed) {
                const now = Date.now();
                const prev = ed._lastSpace || 0;
                ed._lastSpace = now;
                if (now - prev > 500) return;
                const sel = window.getSelection();
                if (!sel.rangeCount) return;
                let n = sel.anchorNode;
                if (n.nodeType === 3 && sel.anchorOffset > 0) {
                    if (n.textContent[sel.anchorOffset - 1] !== ' ') return;
                }
                const fmt = ['B','I','U','S','EM','STRONG','CODE','A','DEL','MARK','SUB','SUP'];
                if (!fmt.some(t => n.parentNode?.closest?.(t))) return;
                e.preventDefault();
                document.execCommand('removeFormat');
                const txt = document.createTextNode(' ');
                const r = sel.getRangeAt(0);
                r.deleteContents(); r.insertNode(txt);
                r.setStartAfter(txt); r.collapse(true);
                sel.removeAllRanges(); sel.addRange(r);
                this._emit(ed.closest('.ux-rich-text-editor'));
            },

            _blockAt(node, root) {
                while (node && node !== root) {
                    if (node.nodeType === 1) {
                        const t = node.tagName?.toLowerCase();
                        if (/^(p|div|h[1-6]|blockquote|pre|li|td|th|figcaption)$/.test(t)) return node;
                    }
                    node = node.parentNode;
                }
                return null;
            },

            _atEnd(range, block) {
                if (range.startContainer.nodeType === 3) {
                    if (range.startContainer.textContent.slice(range.startOffset).trim()) return false;
                }
                let next = range.startContainer;
                while (next) {
                    if (next === block) return true;
                    if (next.nextSibling) {
                        if (next.nextSibling.nodeType === 3 && next.nextSibling.textContent.trim()) return false;
                        if (next.nextSibling.nodeType === 1 && next.nextSibling.textContent.trim()) return false;
                    }
                    next = next.parentNode;
                }
                return true;
            },

            _insertAfter(el, html, ed, root) {
                const p = document.createElement('div'); p.innerHTML = html;
                const frag = document.createDocumentFragment();
                while (p.firstChild) frag.appendChild(p.firstChild);
                el.parentNode.insertBefore(frag, el.nextSibling);
                const last = el.nextSibling;
                if (last) {
                    const sel = window.getSelection();
                    const r = document.createRange();
                    r.setStart(last, 0); r.collapse(true);
                    sel.removeAllRanges(); sel.addRange(r);
                    ed.focus();
                }
                this._emit(root);
            },

            _unwrapBlock(block) {
                const p = document.createElement('p');
                while (block.firstChild) p.appendChild(block.firstChild);
                if (!p.innerHTML.trim()) p.innerHTML = '<br>';
                block.replaceWith(p);
            },

            _clean(h) {
                const d = document.createElement('div'); d.innerHTML = h;
                ['script','style','iframe','object','embed','applet','meta','link','base','form','input','button','select','textarea'].forEach(t => d.querySelectorAll(t).forEach(e => e.remove()));
                d.querySelectorAll('*').forEach(e => Array.from(e.attributes).forEach(a => { if (/^on/i.test(a.name)) e.removeAttribute(a.name); }));
                return d.innerHTML;
            },

            liveHandler(op) {
                const el = document.getElementById(op.id);
                if (!el) return;
                const ed = el.querySelector('.ux-editor-content');
                if (!ed && op.action !== 'getContent') return;
                switch (op.action) {
                    case 'getContent': return ed?.innerHTML || '';
                    case 'setContent': ed.innerHTML = op.value || ''; this._emit(el); break;
                    case 'insertHTML': ed.focus(); document.execCommand('insertHTML', false, op.html || ''); this._emit(el); break;
                    case 'execCommand': ed.focus(); document.execCommand(op.command, false, op.value || null); this._emit(el); break;
                    case 'focus': ed.focus(); break;
                }
            },
        };

        if (window.UX?.register) window.UX.register('rich-text-editor', RTE);
        return RTE;
JS;
    }

    // ─── CSS ──────────────────────────────────────────────

    private function css(): string
    {
        return <<<'CSS'
.ux-rich-text-editor { border:1px solid #d1d5db; border-radius:.5rem; overflow:hidden; background:#fff; display:flex; flex-direction:column; }
.ux-editor-toolbar { display:flex; flex-wrap:wrap; align-items:center; padding:6px 8px; border-bottom:1px solid #e5e7eb; background:#f9fafb; gap:6px; position:relative; }
.ux-editor-tb-group { display:flex; align-items:center; gap:1px; }
.ux-editor-sep { display:inline-block; width:1px; height:20px; background:#d1d5db; margin:0 2px; flex-shrink:0; }
.ux-editor-btn { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border:1px solid transparent; border-radius:4px; background:transparent; color:#4b5563; cursor:pointer; transition:background .15s,color .15s; flex-shrink:0; padding:0; }
.ux-editor-btn:hover { background:#e5e7eb; color:#111827; }
.ux-editor-select { height:32px; padding:0 8px; border:1px solid #d1d5db; border-radius:4px; background:#fff; color:#4b5563; font-size:13px; cursor:pointer; outline:none; min-width:80px; }
.ux-editor-select:focus { border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.15); }
.ux-editor-color-wrap { position:relative; display:inline-flex; }
.ux-editor-color-picker { display:none; position:absolute; top:100%; left:0; z-index:100; background:#fff; border:1px solid #d1d5db; border-radius:6px; padding:6px; box-shadow:0 4px 16px rgba(0,0,0,.12); width:186px; flex-wrap:wrap; gap:2px; margin-top:4px; }
.ux-editor-color-wrap:focus-within .ux-editor-color-picker,.ux-editor-color-wrap:hover .ux-editor-color-picker { display:flex; }
.ux-editor-color-swatch { width:18px; height:18px; border:1px solid rgba(0,0,0,.12); border-radius:2px; cursor:pointer; padding:0; transition:transform .1s; }
.ux-editor-color-swatch:hover { transform:scale(1.2); z-index:1; }
.ux-editor-color-input { width:100%; height:22px; border:1px solid #d1d5db; border-radius:3px; padding:0 2px; cursor:pointer; margin-top:4px; }

/* ── Modal ── */
.ux-editor-modal-backdrop { position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.4); display:flex; align-items:center; justify-content:center; }
.ux-editor-modal { background:#fff; border-radius:10px; box-shadow:0 20px 60px rgba(0,0,0,.2); width:420px; max-width:92vw; max-height:90vh; overflow-y:auto; }
.ux-editor-modal-header { display:flex; align-items:center; gap:8px; padding:16px 18px 12px; font-size:15px; font-weight:600; color:#374151; border-bottom:1px solid #f3f4f6; }
.ux-editor-modal-header svg { color:#6b7280; flex-shrink:0; }
.ux-editor-modal-close { margin-left:auto; background:none; border:none; cursor:pointer; color:#9ca3af; padding:4px; border-radius:4px; }
.ux-editor-modal-close:hover { color:#374151; background:#f3f4f6; }
.ux-editor-modal-body { display:flex; flex-direction:column; gap:8px; padding:14px 18px; }
.ux-editor-modal-input { width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:5px; font-size:14px; outline:none; box-sizing:border-box; }
.ux-editor-modal-input:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.12); }
.ux-editor-modal-btns { display:flex; gap:8px; justify-content:flex-end; padding:12px 18px 16px; border-top:1px solid #f3f4f6; }
.ux-editor-modal-btn { padding:7px 18px; border-radius:5px; font-size:14px; cursor:pointer; border:1px solid #d1d5db; font-weight:500; }
.ux-editor-modal-cancel { background:#fff; color:#6b7280; }
.ux-editor-modal-cancel:hover { background:#f3f4f6; }
.ux-editor-modal-confirm { background:#3b82f6; color:#fff; border-color:#3b82f6; }
.ux-editor-modal-confirm:hover { background:#2563eb; }
.ux-editor-modal-media-btn { background:#fff; color:#3b82f6; border:1px dashed #3b82f6; margin-right:auto; }
.ux-editor-modal-media-btn:hover { background:#eff6ff; }

/* ── 图片弹窗 Tabs ── */
.ux-editor-modal-tabs { display:flex; border-bottom:1px solid #f3f4f6; padding:0 18px; }
.ux-editor-modal-tab { background:none; border:none; padding:8px 16px; font-size:13px; color:#6b7280; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-1px; transition:color .15s,border-color .15s; }
.ux-editor-modal-tab:hover { color:#374151; }
.ux-editor-modal-tab.active { color:#3b82f6; border-bottom-color:#3b82f6; }
.ux-editor-modal-tab-panel { display:none; flex-direction:column; gap:8px; }
.ux-editor-modal-tab-panel.active { display:flex; }

/* ── 媒体库网格 ── */
.ux-editor-media-search { margin-bottom:4px; }
.ux-editor-media-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(80px, 1fr)); gap:6px; max-height:260px; overflow-y:auto; }
.ux-editor-media-item { aspect-ratio:1; border:1px solid #e5e7eb; border-radius:4px; overflow:hidden; cursor:pointer; transition:transform .1s,border-color .15s; display:flex; align-items:center; justify-content:center; background:#f9fafb; }
.ux-editor-media-item:hover { transform:scale(1.05); border-color:#3b82f6; }
.ux-editor-media-item img { width:100%; height:100%; object-fit:cover; }
.ux-editor-media-item-icon { font-size:12px; font-weight:700; color:#9ca3af; }
.ux-editor-media-loading { text-align:center; padding:20px; color:#9ca3af; font-size:13px; }
.ux-editor-media-empty { text-align:center; padding:20px; color:#9ca3af; font-size:13px; grid-column:1/-1; }

.ux-editor-content { flex:1; padding:12px 16px; outline:none; font-size:15px; line-height:1.7; color:#1f2937; overflow-y:auto; word-break:break-word; }
.ux-editor-content:focus { box-shadow:inset 0 0 0 2px #3b82f6; }
.ux-editor-content[data-placeholder]:empty::before { content:attr(data-placeholder); color:#9ca3af; }
.ux-editor-content h1 { font-size:2em; margin:.67em 0; }
.ux-editor-content h2 { font-size:1.5em; margin:.75em 0; }
.ux-editor-content h3 { font-size:1.17em; margin:.83em 0; }
.ux-editor-content h4 { font-size:1em; margin:1em 0; }
.ux-editor-content blockquote { border-left:3px solid #3b82f6; padding:8px 16px; margin:12px 0; color:#6b7280; background:#f9fafb; }
.ux-editor-content pre { background:#1f2937; color:#e5e7eb; padding:12px 16px; border-radius:6px; font-family:Menlo,Monaco,'Courier New',monospace; font-size:14px; overflow-x:auto; }
.ux-editor-content code { background:#f3f4f6; color:#ef4444; padding:2px 6px; border-radius:3px; font-family:Menlo,Monaco,'Courier New',monospace; font-size:.9em; }
.ux-editor-content ul,.ux-editor-content ol { padding-left:24px; margin:8px 0; }
.ux-editor-content a { color:#2563eb; text-decoration:underline; }
.ux-editor-content img { max-width:100%; height:auto; border-radius:4px; margin:8px 0; }
.ux-editor-content hr { border:none; border-top:2px solid #e5e7eb; margin:20px 0; }
.ux-editor-content table { border-collapse:collapse; width:100%; margin:12px 0; }
.ux-editor-content td,.ux-editor-content th { border:1px solid #d1d5db; padding:8px 12px; min-width:60px; }
.ux-editor-content th { background:#f9fafb; font-weight:600; }
.ux-rich-text-editor-readonly .ux-editor-content { background:#f9fafb; cursor:default; }
.ux-rich-text-editor-readonly .ux-editor-content:focus { box-shadow:none; }
CSS;
    }
}