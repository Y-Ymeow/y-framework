<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\State;
use Framework\CSS\RichEditorRules;
use Framework\UX\RichEditor\BlockRegistry;
use Framework\UX\RichEditor\InlineFormatRegistry;
use Framework\UX\RichEditor\SegmentRenderer;
use Framework\UX\UXLiveComponent;
use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;

class BlockEditor extends UXLiveComponent
{
    #[State]
    public array $blocks = [];

    #[State]
    public int $selectedIndex = -1;

    #[State]
    public bool $previewMode = false;

    private string $fieldName = '';
    private string|array $fieldLabel = '';
    private string $fieldPlaceholder = '';
    private string $fieldHelp = '';
    private bool $fieldRequired = false;
    private array $allowedBlocks = [];
    private string $minHeight = '';
    private string $liveModelProperty = '';

    public function __construct(string $name = '')
    {
        parent::__construct();
        if ($name) {
            $this->fieldName = $name;
        }
        AssetRegistry::getInstance()->inlineStyle('ux:block-editor', RichEditorRules::getBlockEditorStyles());
        BlockRegistry::registerCoreBlocks();
        InlineFormatRegistry::registerCoreFormats();
    }

    public static function make(array|string $props = [], array $routeParams = []): static
    {
        if (is_string($props)) {
            $instance = new static($props);
            $instance->_invoke($routeParams);
            return $instance;
        }
        return parent::make($props, $routeParams);
    }

    public function name(string $name): static
    {
        $this->fieldName = $name;
        return $this;
    }
    public function label(string|array $label): static
    {
        $this->fieldLabel = $label;
        return $this;
    }
    public function placeholder(string $placeholder): static
    {
        $this->fieldPlaceholder = $placeholder;
        return $this;
    }
    public function help(string $help): static
    {
        $this->fieldHelp = $help;
        return $this;
    }
    public function required(bool $required = true): static
    {
        $this->fieldRequired = $required;
        return $this;
    }
    public function allowedBlocks(array $blocks): static
    {
        $this->allowedBlocks = $blocks;
        return $this;
    }
    public function minHeight(string $height): static
    {
        $this->minHeight = $height;
        return $this;
    }
    public function height(string $height): static
    {
        return $this->minHeight($height);
    }
    public function liveModel(string $property): static
    {
        $this->liveModelProperty = $property;
        return $this;
    }

    public function value(mixed $value): static
    {
        if (is_string($value) && $value !== '') {
            $parsed = BlockRegistry::parse($value);
            if (!empty($parsed)) {
                $this->blocks = $parsed;
            }
        }
        return $this;
    }

    public function mount(): void
    {
        if (empty($this->blocks)) {
            $this->blocks[] = [
                'blockName' => 'paragraph',
                'attributes' => ['content' => ''],
            ];
        }
    }

    #[LiveAction]
    public function addBlock(string $blockName): void
    {
        $formData = [
            'blockName' => $blockName,
        ];

        $this->mergeCurrentBlocks($formData);
        $blockName = $formData['blockName'] ?: 'paragraph';
        $blockType = BlockRegistry::get($blockName);
        $defaults = $blockType ? $blockType->getDefaultAttributes() : [];
        if (isset($defaults['content']) && is_array($defaults['content'])) {
            $defaults['content'] = '';
        }
        $this->blocks[] = ['blockName' => $blockName, 'attributes' => $defaults];
        $this->selectedIndex = count($this->blocks) - 1;
        $this->refresh('canvas');
    }

    #[LiveAction]
    public function deleteBlock(array $params): void
    {
        $index = (int)($params['index'] ?? -1);
        if ($index >= 0 && $index < count($this->blocks)) {
            array_splice($this->blocks, $index, 1);
        }
        if ($this->selectedIndex >= count($this->blocks)) {
            $this->selectedIndex = count($this->blocks) - 1;
        }
        $this->refresh('canvas');
    }

    #[LiveAction]
    public function moveBlock(array $params): void
    {
        $index = (int)($params['index'] ?? -1);
        $direction = (int)($params['direction'] ?? 1);
        $newIndex = $index + $direction;
        if ($index < 0 || $index >= count($this->blocks)) return;
        if ($newIndex < 0 || $newIndex >= count($this->blocks)) return;
        $tmp = $this->blocks[$index];
        $this->blocks[$index] = $this->blocks[$newIndex];
        $this->blocks[$newIndex] = $tmp;
        $this->selectedIndex = $newIndex;
        $this->refresh('canvas');
    }

    #[LiveAction]
    public function reorderBlocks(array $params): void
    {
        $ordered = $params['blocks'] ?? [];
        if (!empty($ordered)) {
            $this->blocks = $ordered;
        }
        $this->refresh('canvas');
    }

    #[LiveAction]
    public function updateBlockAttr(array $params): void
    {
        $index = (int)($params['index'] ?? -1);
        $attr = $params['attr'] ?? '';
        $value = $params['value'] ?? '';
        if ($index < 0 || $index >= count($this->blocks) || !$attr) return;
        $this->blocks[$index]['attributes'][$attr] = $value;
    }

    #[LiveAction]
    public function selectBlock(array $params): void
    {
        $this->selectedIndex = (int)($params['index'] ?? -1);
    }

    #[LiveAction]
    public function updateListItem(array $params): void
    {
        $index = (int)($params['index'] ?? -1);
        $items = $params['items'] ?? [];
        if ($index >= 0 && $index < count($this->blocks)) {
            $this->blocks[$index]['attributes']['items'] = $items;
        }
        $this->refresh('canvas');
    }

    #[LiveAction]
    public function updateColumns(array $params): void
    {
        $index = (int)($params['index'] ?? -1);
        $colCount = (int)($params['colCount'] ?? 2);
        $colIndex = (int)($params['colIndex'] ?? 0);
        $content = $params['content'] ?? '';
        if ($index >= 0 && $index < count($this->blocks)) {
            $cols = $this->blocks[$index]['attributes']['columns'] ?? [];
            while (count($cols) < $colCount) {
                $cols[] = ['content' => ''];
            }
            $cols[$colIndex]['content'] = $content;
            $this->blocks[$index]['attributes']['columns'] = $cols;
        }
    }

    #[LiveAction]
    public function updateTable(array $params): void
    {
        $index = (int)($params['index'] ?? -1);
        $type = $params['type'] ?? 'cell';
        $row = (int)($params['row'] ?? 0);
        $col = (int)($params['col'] ?? 0);
        $value = $params['value'] ?? '';
        if ($index >= 0 && $index < count($this->blocks)) {
            if ($type === 'header') {
                $this->blocks[$index]['attributes']['headers'][$col] = $value;
            } else {
                if (!isset($this->blocks[$index]['attributes']['rows'][$row])) {
                    $this->blocks[$index]['attributes']['rows'][$row] = [];
                }
                $this->blocks[$index]['attributes']['rows'][$row][$col] = $value;
            }
        }
    }

    #[LiveAction]
    public function addTableRow(array $params): void
    {
        $index = (int)($params['index'] ?? -1);
        if ($index >= 0 && $index < count($this->blocks)) {
            $colCount = count($this->blocks[$index]['attributes']['headers'] ?? ['列1', '列2', '列3']);
            $this->blocks[$index]['attributes']['rows'][] = array_fill(0, $colCount, '');
        }
        $this->refresh('canvas');
    }

    #[LiveAction]
    public function addTableCol(array $params): void
    {
        $index = (int)($params['index'] ?? -1);
        if ($index >= 0 && $index < count($this->blocks)) {
            $this->blocks[$index]['attributes']['headers'][] = '新列';
            foreach ($this->blocks[$index]['attributes']['rows'] as &$row) {
                $row[] = '';
            }
            unset($row);
        }
        $this->refresh('canvas');
    }

    #[LiveAction]
    public function deleteTableRow(array $params): void
    {
        $index = (int)($params['index'] ?? -1);
        $row = (int)($params['row'] ?? -1);
        if ($index >= 0 && $index < count($this->blocks)) {
            array_splice($this->blocks[$index]['attributes']['rows'], $row, 1);
        }
        $this->refresh('canvas');
    }

    #[LiveAction]
    public function changeColumnCount(array $params): void
    {
        $index = (int)($params['index'] ?? -1);
        $count = (int)($params['count'] ?? 2);
        if ($index >= 0 && $index < count($this->blocks)) {
            $cols = $this->blocks[$index]['attributes']['columns'] ?? [];
            while (count($cols) < $count) {
                $cols[] = ['content' => ''];
            }
            $this->blocks[$index]['attributes']['columns'] = array_slice($cols, 0, $count);
            $this->blocks[$index]['attributes']['columnCount'] = $count;
        }
        $this->refresh('canvas');
    }

    protected function mergeCurrentBlocks(array $formData): void
    {
        if (!empty($formData['currentBlocks'])) {
            $blocksData = json_decode($formData['currentBlocks'], true);
            if (is_array($blocksData)) {
                $this->blocks = array_values(array_filter($blocksData, fn($b) => !empty($b['blockName'])));
            }
        }
    }

    #[LiveAction]
    public function togglePreview(array $formData = []): void
    {
        $this->mergeCurrentBlocks($formData);
        $this->previewMode = !$this->previewMode;
        $this->refresh('canvas');
    }

    public function render(): Element
    {
        $group = Element::make('div')->class('ux-block-editor');

        $label = $this->renderLabel();
        if ($label) {
            $group->child($label);
        }

        $group->child($this->renderToolbar());
        $group->child($this->renderCanvas());

        $hidden = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', $this->fieldName)
            ->attr('id', $this->fieldName)
            ->attr('value', json_encode($this->blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $submitBlockName = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('data-submit-field', 'blockName')
            ->attr('value', '');

        $submitBlocks = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('data-submit-field', 'currentBlocks')
            ->attr('value', '');

        if ($this->fieldRequired) {
            $hidden->attr('required', 'required');
        }

        $group->child($hidden);
        $group->child($submitBlockName);
        $group->child($submitBlocks);

        if ($this->fieldHelp) {
            $group->child(Element::make('div')->class('ux-form-help')->text($this->fieldHelp));
        }

        if ($this->liveModelProperty) {
            $group->attr('data-live-model', $this->liveModelProperty);
        }

        return $group;
    }

    protected function renderLabel(): ?Element
    {
        if (!$this->fieldLabel) return null;
        $label = Element::make('label')->class('ux-form-label')->attr('for', $this->fieldName);
        if (is_array($this->fieldLabel)) {
            $label->intl(...$this->fieldLabel);
        } else {
            $label->text($this->fieldLabel);
        }
        if ($this->fieldRequired) {
            $label->child(Element::make('span')->class('ux-form-required')->text('*'));
        }
        return $label;
    }

    protected function renderToolbar(): Element
    {
        $toolbar = Element::make('div')->class('ux-block-editor__toolbar');

        $previewBtn = Element::make('button')
            ->class('ux-block-editor__toolbar-btn')
            ->attr('type', 'button')
            ->attr('title', $this->previewMode ? '编辑模式' : '预览')
            ->attr('data-submit:click', 'togglePreview')
            ->attr('data-block-name', '__preview__');
        $previewBtn->html($this->previewMode
            ? '<i class="bi bi-pencil"></i>'
            : '<i class="bi bi-eye"></i>');
        $toolbar->child($previewBtn);

        $toolbar->child(Element::make('span')->class('ux-block-editor__toolbar-sep'));

        $textBtns = [
            ['paragraph', '段落', 'bi-type'],
            ['heading', '标题', 'bi-type-h1'],
            ['quote', '引用', 'bi-chat-quote'],
            ['code', '代码', 'bi-code'],
            ['list', '列表', 'bi-list-ul'],
        ];
        foreach ($textBtns as [$name, $title, $icon]) {
            $toolbar->child($this->makeInsertBtn($name, $title, $icon));
        }

        $toolbar->child(Element::make('span')->class('ux-block-editor__toolbar-sep'));

        $mediaBtns = [
            ['image', '图片', 'bi-image'],
            ['video', '视频', 'bi-camera-video'],
        ];
        foreach ($mediaBtns as [$name, $title, $icon]) {
            $toolbar->child($this->makeInsertBtn($name, $title, $icon));
        }

        $toolbar->child(Element::make('span')->class('ux-block-editor__toolbar-sep'));

        $layoutBtns = [
            ['columns', '列数', 'bi-columns'],
            ['callout', '提示', 'bi-info-circle'],
            ['table', '表格', 'bi-grid-3x3'],
            ['divider', '分隔线', 'bi-dash-lg'],
        ];
        foreach ($layoutBtns as [$name, $title, $icon]) {
            $toolbar->child($this->makeInsertBtn($name, $title, $icon));
        }

        return $toolbar;
    }

    protected function makeInsertBtn(string $blockName, string $title, string $icon): Element
    {
        return Element::make('button')
            ->class('ux-block-editor__toolbar-btn')
            ->attr('type', 'button')
            ->attr('title', $title)
            ->liveAction('addBlock', 'click', ['blockName' => $blockName])
            ->attr('data-block-name', $blockName)
            ->html('<i class="bi ' . $icon . '"></i>');
    }

    protected function renderCanvas(): Element
    {
        $canvas = Element::make('div')
            ->class('ux-block-editor__canvas', $this->previewMode ? 'ux-block-editor__canvas--preview' : '')
            ->state(['block' => json_decode('{}')])
            ->liveFragment('canvas');

        if ($this->minHeight) {
            $canvas->style('min-height', $this->minHeight);
        }

        if (empty($this->blocks)) {
            $canvas->child(
                Element::make('div')->class('ux-block-editor__empty')
                    ->text($this->fieldPlaceholder ?: '点击上方按钮添加内容块')
            );
            return $canvas;
        }

        foreach ($this->blocks as $i => $block) {
            if ($this->previewMode) {
                $canvas->child($this->renderBlockPreview($i, $block));
            } else {
                $canvas->child($this->renderBlock($i, $block));
            }
        }

        return $canvas;
    }

    protected function renderBlock(int $index, array $block): Element
    {
        $isSelected = $index === $this->selectedIndex;
        $blockName = $block['blockName'] ?: 'paragraph';

        $wrapper = Element::make('div')
            ->class('ux-block-editor__block', $isSelected ? 'ux-block-editor__block--selected' : '')
            ->data('block-index', (string)$index)
            ->data('block-name', $blockName);

        $toolbar = Element::make('div')->class('ux-block-editor__block-toolbar');

        $toolbarLeft = Element::make('div')->class('ux-block-editor__block-toolbar-left');

        $toolbarLeft->child(Element::make('button')
            ->class('ux-block-editor__block-handle-btn')
            ->attr('type', 'button')
            ->attr('data-drag-handle', '')
            ->html('<i class="bi bi-grip-vertical"></i>'));

        $blockType = BlockRegistry::get($blockName);
        if ($blockType) {
            $toolbarLeft->child(
                Element::make('span')->class('ux-block-editor__block-type-label')->text($blockType->title ?: $blockName)
            );
        }

        $toolbarRight = Element::make('div')->class('ux-block-editor__block-toolbar-right');

        if ($index > 0) {
            $toolbarRight->child(Element::make('button')
                ->class('ux-block-editor__block-action-btn')
                ->attr('type', 'button')
                ->liveAction('moveBlock', 'click', ['index' => $index, 'direction' => -1])
                ->html('<i class="bi bi-chevron-up"></i>'));
        }
        if ($index < count($this->blocks) - 1) {
            $toolbarRight->child(Element::make('button')
                ->class('ux-block-editor__block-action-btn')
                ->attr('type', 'button')
                ->liveAction('moveBlock', 'click', ['index' => $index, 'direction' => 1])
                ->html('<i class="bi bi-chevron-down"></i>'));
        }
        $toolbarRight->child(Element::make('button')
            ->class('ux-block-editor__block-action-btn', 'ux-block-editor__block-action-btn--danger')
            ->attr('type', 'button')
            ->liveAction('deleteBlock', 'click', ['index' => $index])
            ->html('<i class="bi bi-trash"></i>'));

        $toolbar->child($toolbarLeft);
        $toolbar->child($toolbarRight);
        $wrapper->child($toolbar);

        $content = Element::make('div')
            ->class('ux-block-editor__block-content')
            ->liveAction('selectBlock', 'click', ['index' => $index]);

        $this->renderBlockContent($content, $index, $block);

        $wrapper->child($content);

        return $wrapper;
    }

    protected function renderBlockPreview(int $index, array $block): Element
    {
        $blockName = $block['blockName'] ?: 'paragraph';
        $attrs = $block['attributes'] ?? [];
        $blockType = BlockRegistry::get($blockName);

        $wrapper = Element::make('div')
            ->class('ux-block-editor__block', 'ux-block-editor__block--preview');

        if ($blockType) {
            $wrapper->child($blockType->renderElement($attrs));
        } else {
            $wrapper->child(Element::make('div')->class('ux-block-editor__block-unknown')
                ->text('Unknown block: ' . $blockName));
        }

        return $wrapper;
    }

    protected function renderBlockContent(Element $container, int $index, array $block): void
    {
        $blockName = $block['blockName'] ?: 'paragraph';
        $attrs = $block['attributes'] ?? [];

        switch ($blockName) {
            case 'paragraph':
                $this->renderRichTextArea($container, $index, 'content', $attrs['content'] ?? '', '输入段落文字...');
                break;
            case 'heading':
                $this->renderHeadingArea($container, $index, $attrs);
                break;
            case 'image':
                $this->renderImageArea($container, $index, $attrs);
                break;
            case 'quote':
                $this->renderRichTextArea($container, $index, 'content', $attrs['content'] ?? '', '输入引用内容...');
                $this->renderTextInput($container, $index, 'cite', $attrs['cite'] ?? '', '引用来源');
                break;
            case 'code':
                $this->renderTextInput($container, $index, 'language', $attrs['language'] ?? '', '语言');
                $this->renderCodeArea($container, $index, 'content', $attrs['content'] ?? '');
                break;
            case 'list':
                $this->renderListArea($container, $index, $attrs);
                break;
            case 'divider':
                $container->child(Element::make('hr')->class('ux-block-editor__divider'));
                break;
            case 'columns':
                $this->renderColumnsArea($container, $index, $attrs);
                break;
            case 'callout':
                $this->renderCalloutArea($container, $index, $attrs);
                break;
            case 'table':
                $this->renderTableArea($container, $index, $attrs);
                break;
            case 'video':
                $this->renderVideoArea($container, $index, $attrs);
                break;
            default:
                $this->renderGenericArea($container, $index, $block);
                break;
        }
    }

    protected function renderRichTextArea(Element $container, int $index, string $attrName, mixed $content, string $placeholder): void
    {
        $container->child(
            Element::make('div')
                ->class('ux-block-editor__editable')
                ->state(['content' => null])
                ->model('content')
                ->liveAction('updateBlockAttr', 'input', ['index' => $index, 'attr' => 'content', 'value' => '$content'])
                ->attr('contenteditable', 'true')
                ->attr('data-editable-index', (string)$index)
                ->attr('data-editable-attr', $attrName)
                ->attr('data-editable-placeholder', $placeholder)
                ->html($this->contentToHtml($content))
        );
    }

    protected function renderHeadingArea(Element $container, int $index, array $attrs): void
    {
        $level = $attrs['level'] ?? 2;
        $select = Element::make('select')
            ->class('ux-block-editor__select')
            ->attr('data-attr-index', (string)$index)
            ->attr('data-attr-name', 'level');
        for ($i = 1; $i <= 6; $i++) {
            $opt = Element::make('option')->attr('value', (string)$i)->text('H' . $i);
            if ($i === (int)$level) $opt->attr('selected', 'selected');
            $select->child($opt);
        }
        $container->child($select);

        $container->child(
            Element::make('div')
                ->class('ux-block-editor__editable')
                ->attr('contenteditable', 'true')
                ->attr('data-editable-index', (string)$index)
                ->attr('data-editable-attr', 'content')
                ->attr('data-editable-placeholder', '输入标题...')
                ->html($this->contentToHtml($attrs['content'] ?? ''))
                ->style('font-size:' . match ((int)$level) {
                    1 => '2rem',
                    2 => '1.5rem',
                    3 => '1.25rem',
                    4 => '1.1rem',
                    5 => '1rem',
                    default => '0.9rem'
                } . ';font-weight:600')
        );
    }

    protected function renderImageArea(Element $container, int $index, array $attrs): void
    {
        $src = $attrs['src'] ?? '';
        if ($src) {
            $container->child(
                Element::make('div')->class('ux-block-editor__image-preview')
                    ->child(Element::make('img')->attr('src', $src)->attr('alt', $attrs['alt'] ?? ''))
            );
        }

        $btn = Element::make('button')
            ->class('ux-block-editor__image-pick-btn')
            ->attr('type', 'button')
            ->attr('data-media-pick', '')
            ->attr('data-block-index', (string)$index)
            ->text($src ? '更换图片' : '选择图片');
        $container->child($btn);

        if ($src) {
            $container->child(
                Element::make('button')
                    ->class('ux-block-editor__image-remove-btn')
                    ->attr('type', 'button')
                    ->liveAction('updateBlockAttr', 'click', ['index' => $index, 'attr' => 'src', 'value' => ''])
                    ->text('移除')
            );
        }

        $container->child(
            Element::make('input')
                ->class('ux-block-editor__input')
                ->attr('type', 'text')
                ->attr('placeholder', '或手动输入图片地址')
                ->attr('value', $src)
                ->attr('data-attr-index', (string)$index)
                ->attr('data-attr-name', 'src')
        );

        $container->child(
            Element::make('input')
                ->class('ux-block-editor__input')
                ->attr('type', 'text')
                ->attr('placeholder', '替代文字')
                ->attr('value', $attrs['alt'] ?? '')
                ->attr('data-attr-index', (string)$index)
                ->attr('data-attr-name', 'alt')
        );

        $container->child(
            Element::make('input')
                ->class('ux-block-editor__input')
                ->attr('type', 'text')
                ->attr('placeholder', '图片说明')
                ->attr('value', $attrs['caption'] ?? '')
                ->attr('data-attr-index', (string)$index)
                ->attr('data-attr-name', 'caption')
        );
    }

    protected function renderCodeArea(Element $container, int $index, string $attrName, mixed $content): void
    {
        $container->child(
            Element::make('pre')
                ->class('ux-block-editor__editable', 'ux-block-editor__editable--code')
                ->attr('contenteditable', 'true')
                ->attr('data-editable-index', (string)$index)
                ->attr('data-editable-attr', $attrName)
                ->attr('data-editable-placeholder', '输入代码...')
                ->text(is_string($content) ? $content : '')
        );
    }

    protected function renderTextInput(Element $container, int $index, string $attrName, mixed $value, string $placeholder): void
    {
        $container->child(
            Element::make('input')
                ->class('ux-block-editor__input')
                ->attr('type', 'text')
                ->attr('placeholder', $placeholder)
                ->attr('value', is_string($value) ? $value : '')
                ->attr('data-attr-index', (string)$index)
                ->attr('data-attr-name', $attrName)
        );
    }

    protected function renderSelect(Element $container, int $index, string $attrName, mixed $currentValue, array $options): void
    {
        $select = Element::make('select')
            ->class('ux-block-editor__select')
            ->attr('data-attr-index', (string)$index)
            ->attr('data-attr-name', $attrName);
        foreach ($options as $value => $label) {
            $opt = Element::make('option')->attr('value', (string)$value)->text($label);
            if ((string)$value === (string)$currentValue) $opt->attr('selected', 'selected');
            $select->child($opt);
        }
        $container->child($select);
    }

    protected function renderListArea(Element $container, int $index, array $attrs): void
    {
        $ordered = $attrs['ordered'] ?? false;

        $toggleLabel = Element::make('label')->class('ux-block-editor__checkbox-label');
        $checkbox = Element::make('input')
            ->attr('type', 'checkbox')
            ->attr('data-attr-index', (string)$index)
            ->attr('data-attr-name', 'ordered');
        if ($ordered) $checkbox->attr('checked', 'checked');
        $toggleLabel->child($checkbox);
        $toggleLabel->text(' 有序列表');
        $container->child($toggleLabel);

        $items = $attrs['items'] ?? [];

        $listEl = Element::make('div')
            ->class('ux-block-editor__list-items')
            ->attr('data-list-index', (string)$index);

        foreach ($items as $i => $item) {
            $row = Element::make('div')->class('ux-block-editor__list-item');
            $row->child(
                Element::make('div')
                    ->class('ux-block-editor__editable', 'ux-block-editor__editable--list')
                    ->attr('contenteditable', 'true')
                    ->attr('data-editable-index', (string)$index)
                    ->attr('data-editable-attr', 'listItem')
                    ->attr('data-editable-subindex', (string)$i)
                    ->attr('data-editable-placeholder', '列表项')
                    ->text(is_string($item) ? $item : '')
            );
            $row->child(
                Element::make('button')
                    ->class('ux-block-editor__list-item-remove')
                    ->attr('type', 'button')
                    ->attr('data-list-remove', (string)$i)
                    ->text('✕')
            );
            $listEl->child($row);
        }

        $container->child($listEl);

        $container->child(
            Element::make('button')
                ->class('ux-block-editor__list-add')
                ->attr('type', 'button')
                ->liveAction('updateListItem', 'click', [
                    'index' => $index,
                    'items' => array_merge($items, ['']),
                ])
                ->text('+ 添加列表项')
        );
    }

    protected function renderColumnsArea(Element $container, int $index, array $attrs): void
    {
        $colCount = (int)($attrs['columnCount'] ?? 2);
        $columns = $attrs['columns'] ?? [];

        $configRow = Element::make('div')->class('ux-block-editor__columns-config');
        $configRow->child(Element::make('label')->class('ux-block-editor__field-label')->text('列数'));
        $select = Element::make('select')
            ->class('ux-block-editor__select')
            ->attr('data-col-count-index', (string)$index);
        for ($i = 1; $i <= 4; $i++) {
            $opt = Element::make('option')->attr('value', (string)$i)->text($i . ' 列');
            if ($i === $colCount) $opt->attr('selected', 'selected');
            $select->child($opt);
        }
        $configRow->child($select);
        $container->child($configRow);

        $colsWrapper = Element::make('div')
            ->class('ux-block-editor__columns-wrapper')
            ->style("display:grid;grid-template-columns:repeat({$colCount},1fr);gap:0.5rem");

        for ($i = 0; $i < $colCount; $i++) {
            $colContent = $columns[$i]['content'] ?? '';
            $colsWrapper->child(
                Element::make('div')
                    ->class('ux-block-editor__editable', 'ux-block-editor__editable--column')
                    ->attr('contenteditable', 'true')
                    ->attr('data-editable-index', (string)$index)
                    ->attr('data-editable-attr', 'column')
                    ->attr('data-editable-subindex', (string)$i)
                    ->attr('data-editable-placeholder', '输入内容...')
                    ->html(is_string($colContent) && $colContent !== '' ? $colContent : $this->contentToHtml($colContent))
            );
        }

        $container->child($colsWrapper);
    }

    protected function renderCalloutArea(Element $container, int $index, array $attrs): void
    {
        $type = $attrs['type'] ?? 'info';
        $this->renderSelect($container, $index, 'type', $type, [
            'info' => '信息',
            'warning' => '警告',
            'success' => '成功',
            'danger' => '危险',
            'tip' => '提示',
        ]);

        $container->child(
            Element::make('input')
                ->class('ux-block-editor__input')
                ->attr('type', 'text')
                ->attr('placeholder', '标题（可选）')
                ->attr('value', $attrs['title'] ?? '')
                ->attr('data-attr-index', (string)$index)
                ->attr('data-attr-name', 'title')
        );

        $container->child(
            Element::make('div')
                ->class('ux-block-editor__editable')
                ->attr('contenteditable', 'true')
                ->attr('data-editable-index', (string)$index)
                ->attr('data-editable-attr', 'content')
                ->attr('data-editable-placeholder', '输入提示内容...')
                ->html($this->contentToHtml($attrs['content'] ?? ''))
        );
    }

    protected function renderTableArea(Element $container, int $index, array $attrs): void
    {
        $headers = $attrs['headers'] ?? [];
        $rows = $attrs['rows'] ?? [];

        $configRow = Element::make('div')->class('ux-block-editor__table-config');
        $configRow->child(
            Element::make('button')->class('ux-block-editor__list-add')->attr('type', 'button')
                ->liveAction('addTableCol', 'click', ['index' => $index])
                ->text('+ 添加列')
        );
        $configRow->child(
            Element::make('button')->class('ux-block-editor__list-add')->attr('type', 'button')
                ->liveAction('addTableRow', 'click', ['index' => $index])
                ->text('+ 添加行')
        );
        $container->child($configRow);

        $table = Element::make('table')->class('ux-block-editor__table');
        $thead = Element::make('thead');
        $headRow = Element::make('tr');
        foreach ($headers as $ci => $header) {
            $headRow->child(
                Element::make('th')->child(
                    Element::make('div')
                        ->class('ux-block-editor__table-cell')
                        ->attr('contenteditable', 'true')
                        ->attr('data-table-index', (string)$index)
                        ->attr('data-table-type', 'header')
                        ->attr('data-table-col', (string)$ci)
                        ->attr('data-table-placeholder', '表头')
                        ->text(is_string($header) ? $header : '')
                )
            );
        }
        $thead->child($headRow);
        $table->child($thead);

        $tbody = Element::make('tbody');
        foreach ($rows as $ri => $row) {
            $tr = Element::make('tr');
            foreach ((array)$row as $ci => $cell) {
                $tr->child(
                    Element::make('td')->child(
                        Element::make('div')
                            ->class('ux-block-editor__table-cell')
                            ->attr('contenteditable', 'true')
                            ->attr('data-table-index', (string)$index)
                            ->attr('data-table-type', 'cell')
                            ->attr('data-table-row', (string)$ri)
                            ->attr('data-table-col', (string)$ci)
                            ->attr('data-table-placeholder', ' ')
                            ->text(is_string($cell) ? $cell : '')
                    )
                );
            }
            $tr->child(
                Element::make('td')->class('ux-block-editor__table-del')->child(
                    Element::make('button')
                        ->attr('type', 'button')
                        ->liveAction('deleteTableRow', 'click', ['index' => $index, 'row' => $ri])
                        ->text('✕')
                )
            );
            $tbody->child($tr);
        }
        $table->child($tbody);
        $container->child($table);
    }

    protected function renderVideoArea(Element $container, int $index, array $attrs): void
    {
        $type = $attrs['type'] ?? 'embed';
        $this->renderSelect($container, $index, 'type', $type, [
            'embed' => '嵌入链接',
            'native' => '本地视频',
        ]);

        $container->child(
            Element::make('input')
                ->class('ux-block-editor__input')
                ->attr('type', 'text')
                ->attr('placeholder', $type === 'embed' ? 'YouTube / Bilibili / Vimeo 链接' : '视频文件地址')
                ->attr('value', $attrs['src'] ?? '')
                ->attr('data-attr-index', (string)$index)
                ->attr('data-attr-name', 'src')
        );

        $container->child(
            Element::make('input')
                ->class('ux-block-editor__input')
                ->attr('type', 'text')
                ->attr('placeholder', '视频说明（可选）')
                ->attr('value', $attrs['caption'] ?? '')
                ->attr('data-attr-index', (string)$index)
                ->attr('data-attr-name', 'caption')
        );
    }

    protected function renderGenericArea(Element $container, int $index, array $block): void
    {
        $blockType = BlockRegistry::get($block['blockName'] ?? '');
        $attrs = $block['attributes'] ?? [];
        $attrDefs = $blockType ? $blockType->attributes : [];

        foreach ($attrDefs as $attrName => $def) {
            $type = $def['type'] ?? 'string';
            $value = $attrs[$attrName] ?? $def['default'] ?? '';

            $container->child(Element::make('label')->class('ux-block-editor__field-label')->text($attrName));

            if ($type === 'rich-text') {
                $container->child(
                    Element::make('div')
                        ->class('ux-block-editor__editable')
                        ->attr('contenteditable', 'true')
                        ->attr('data-editable-index', (string)$index)
                        ->attr('data-editable-attr', $attrName)
                        ->html($this->contentToHtml($value))
                );
            } elseif ($type === 'boolean') {
                $label = Element::make('label')->class('ux-block-editor__checkbox-label');
                $cb = Element::make('input')
                    ->attr('type', 'checkbox')
                    ->attr('data-attr-index', (string)$index)
                    ->attr('data-attr-name', $attrName);
                if ($value) $cb->attr('checked', 'checked');
                $label->child($cb);
                $label->text(' ' . $attrName);
                $container->child($label);
            } else {
                $container->child(
                    Element::make('input')
                        ->class('ux-block-editor__input')
                        ->attr('type', $type === 'number' ? 'number' : 'text')
                        ->attr('value', is_scalar($value) ? (string)$value : '')
                        ->attr('data-attr-index', (string)$index)
                        ->attr('data-attr-name', $attrName)
                );
            }
        }
    }

    protected function renderInserter(): Element
    {
        $inserter = Element::make('div')
            ->class('ux-block-editor__inserter')
            ->attr('data-inserter-panel', '');

        $blocks = $this->getAvailableBlockDefs();
        $categories = [];
        foreach ($blocks as $def) {
            $cat = $def['category'] ?? 'common';
            $categories[$cat][] = $def;
        }

        foreach ($categories as $catName => $catBlocks) {
            $section = Element::make('div')->class('ux-block-editor__inserter-section');
            $section->child(
                Element::make('div')->class('ux-block-editor__inserter-section-title')->text(ucfirst($catName))
            );
            $grid = Element::make('div')->class('ux-block-editor__inserter-grid');
            foreach ($catBlocks as $def) {
                $item = Element::make('button')
                    ->class('ux-block-editor__inserter-item')
                    ->attr('type', 'button')
                    ->liveAction('addBlock', 'click', ['blockName' => $def['name']]);
                if (!empty($def['icon'])) {
                    $item->child(Element::make('span')->class('ux-block-editor__inserter-item-icon')->html($def['icon']));
                }
                $item->child(Element::make('span')->class('ux-block-editor__inserter-item-label')->text($def['title'] ?: $def['name']));
                $grid->child($item);
            }
            $section->child($grid);
            $inserter->child($section);
        }

        return $inserter;
    }

    protected function getAvailableBlockDefs(): array
    {
        if (!empty($this->allowedBlocks)) {
            $defs = [];
            foreach ($this->allowedBlocks as $name) {
                $bt = BlockRegistry::get($name);
                if ($bt) $defs[$name] = $bt->toArray();
            }
            return $defs;
        }
        return BlockRegistry::allDefinitions();
    }

    protected function contentToHtml(mixed $content): string
    {
        if (is_string($content)) return $content;
        if (is_array($content) && !empty($content)) {
            return SegmentRenderer::segmentsToHtml($content);
        }
        return '';
    }
}
