<?php

declare(strict_types=1);

namespace App\Pages;

use Framework\Component\LiveComponent;
use Framework\Component\Attribute\LiveAction;
use Framework\Routing\Attribute\Get;
use Framework\Routing\Attribute\Route;
use Framework\View\Base\Element;
use Framework\UX\Form\RichEditor;
use Framework\UX\RichEditor\Extensions\MentionExtension;
use Framework\UX\RichEditor\Extensions\EmojiExtension;
use Framework\UX\RichEditor\Extensions\PlaceholderExtension;
use Framework\UX\RichEditor\DocumentParser;
use Framework\UX\UI\Button;

#[Route('/demo/rich-editor')]
class RichEditorDemo extends LiveComponent
{
    use \Framework\UX\RichEditor\HasRichEditor;
    public string $basicContent = '<p>这是一段<b>富文本</b>内容，支持<em>各种格式</em>。</p>';
    public string $minimalContent = '';
    public string $extendedContent = '';
    public string $outputFormat = 'html';
    public string $parsedResult = '';
    public string $markdownOutput = '';
    public string $textOutput = '';
    public bool $showPreview = false;

    public function mount(): void
    {
        $this->minimalContent = '<p>简洁模式编辑器，只显示基本工具栏。</p>';
        $this->extendedContent = '<p>带有扩展功能的编辑器，支持 @提及 和 :emoji: 表情！</p>';
    }

    #[LiveAction]
    public function updateBasic(array $params): void
    {
        $this->basicContent = $params['content'] ?? '';
        $this->refresh();
    }

    #[LiveAction]
    public function updateMinimal(array $params): void
    {
        $this->minimalContent = $params['content'] ?? '';
        $this->refresh();
    }

    #[LiveAction]
    public function updateExtended(array $params): void
    {
        $this->extendedContent = $params['content'] ?? '';
        $this->refresh();
    }

    #[LiveAction]
    public function parseContent(array $params = []): void
    {
        debug($this->basicContent);
        // 自动注入的参数会更新到属性中
        if (isset($params['extendedContent'])) {
            $this->extendedContent = $params['extendedContent'];
        }

        $this->parsedResult = DocumentParser::sanitize($this->basicContent);
        $this->markdownOutput = DocumentParser::htmlToMarkdown($this->extendedContent);
        $this->textOutput = DocumentParser::htmlToText($this->extendedContent);
        $this->showPreview = true;
        $this->refresh('outputContent');
    }

    #[LiveAction]
    public function clearAll(): void
    {
        $this->basicContent = '';
        $this->minimalContent = '';
        $this->extendedContent = '';
        $this->parsedResult = '';
        $this->markdownOutput = '';
        $this->textOutput = '';
        $this->showPreview = false;
        $this->refresh();
    }

    #[Get('/')]
    public function render(): Element
    {
        $wrapper = Element::make('div')
            ->class('rich-editor-demo')
            ->style('max-width: 1200px; margin: 0 auto; padding: 2rem;');

        $wrapper->child($this->renderHeader());
        $wrapper->child($this->renderBasicDemo());
        $wrapper->child($this->renderMinimalDemo());
        $wrapper->child($this->renderExtendedDemo());
        $wrapper->child($this->renderOutputSection());
        $wrapper->child($this->renderActions());

        return $wrapper;
    }

    protected function renderHeader(): Element
    {
        return Element::make('div')
            ->style('margin-bottom: 2rem;')
            ->children(
                Element::make('h1')
                    ->style('font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; color: #111827;')
                    ->text('Rich Editor 演示'),
                Element::make('p')
                    ->style('color: #6b7280; font-size: 1rem;')
                    ->text('基于 PHP 扩展能力的富文本编辑器，无需 JavaScript 文件')
            );
    }

    protected function renderBasicDemo(): Element
    {
        $editor = RichEditor::make()
            ->name('basic_content')
            ->label('基础编辑器 (完整工具栏)')
            ->value($this->basicContent)
            ->liveModel('basicContent')
            ->placeholder('请输入内容...')
            ->rows(8);

        return Element::make('div')
            ->class('demo-section')
            ->style('background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1.5rem;')
            ->children(
                Element::make('h2')
                    ->style('font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: #374151;')
                    ->text('1. 基础编辑器'),
                Element::make('div')
                    ->class('demo-description')
                    ->style('margin-bottom: 1rem; color: #6b7280; font-size: 0.875rem;')
                    ->text('完整工具栏，支持粗体、斜体、下划线、删除线、标题、引用、代码、列表、链接等格式'),
                $editor
            );
    }

    protected function renderMinimalDemo(): Element
    {
        $editor = RichEditor::make()
            ->name('minimal_content')
            ->label('简洁模式编辑器')
            ->value($this->minimalContent)
            ->minimal(true)
            ->liveModel('minimalContent')
            ->placeholder('简洁模式没有工具栏，直接输入...')
            ->rows(6);

        return Element::make('div')
            ->class('demo-section')
            ->style('background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1.5rem;')
            ->children(
                Element::make('h2')
                    ->style('font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: #374151;')
                    ->text('2. 简洁模式'),
                Element::make('div')
                    ->class('demo-description')
                    ->style('margin-bottom: 1rem; color: #6b7280; font-size: 0.875rem;')
                    ->text('minimal() 模式隐藏工具栏，适合只需要基本输入的场景'),
                $editor
            );
    }

    protected function renderExtendedDemo(): Element
    {
        $mentionExt = new MentionExtension('mention', [
            'trigger' => '@',
            'displayField' => 'name',
            'valueField' => 'id',
        ]);
        $mentionExt->setDataSource([
            ['id' => '1', 'name' => '张三'],
            ['id' => '2', 'name' => '李四'],
            ['id' => '3', 'name' => '王五'],
        ]);

        $emojiExt = new EmojiExtension('emoji', [
            'shortcodes' => true,
        ]);
        $emojiExt->setEmojiMap([
            ':php:' => '🐘',
            ':code:' => '💻',
            ':star:' => '⭐',
            ':fire:' => '🔥',
            ':rocket:' => '🚀',
            ':heart:' => '❤️',
        ]);

        $placeholderExt = new PlaceholderExtension('placeholder', [
            'wrapperClass' => 'placeholder-tag',
        ]);
        $placeholderExt->addPlaceholder('username', '用户名', 'Guest')
            ->addPlaceholder('date', '当前日期', date('Y-m-d'))
            ->addPlaceholder('app_name', '应用名称', 'RichEditor');

        $editor = RichEditor::make()
            ->name('extended_content')
            ->label('带扩展的编辑器')
            ->value($this->extendedContent)
            ->liveModel('extendedContent')
            ->placeholder('支持 @提及用户 和 :emoji: 表情符号')
            ->rows(8)
            ->extension('mention', $mentionExt)
            ->extension('emoji', $emojiExt)
            ->extension('placeholder', $placeholderExt);

        return Element::make('div')
            ->class('demo-section')
            ->style('background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1.5rem;')
            ->children(
                Element::make('h2')
                    ->style('font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: #374151;')
                    ->text('3. PHP 扩展功能'),
                Element::make('div')
                    ->class('demo-description')
                    ->style('margin-bottom: 1rem; color: #6b7280; font-size: 0.875rem;')
                    ->html('通过 PHP 扩展实现 @提及、表情符号、变量占位符等功能<br>扩展：MentionExtension, EmojiExtension, PlaceholderExtension'),
                $editor,
                Element::make('div')
                    ->class('extension-info')
                    ->style('margin-top: 1rem; padding: 0.75rem; background: #f3f4f6; border-radius: 0.375rem; font-size: 0.875rem; color: #4b5563;')
                    ->html('<b>可用占位符:</b> {{username}}, {{date}}, {{app_name}}<br><b>可用表情:</b> :php: :code: :star: :fire: :rocket: :heart:')
            );
    }

    protected function renderOutputSection(): Element
    {
        $section = Element::make('div')
            ->class('demo-section')
            ->style('background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 1.5rem;');

        $section->child(
            Element::make('h2')
                ->style('font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: #374151;')
                ->text('4. 输出预览')
        );

        $child = Element::make('div')->liveFragment('outputContent');

        if ($this->showPreview) {
            $child->child(
                Element::make('div')
                    ->class('output-tabs')
                    ->style('display: flex; gap: 0.5rem; margin-bottom: 1rem;')
                    ->children(
                        $this->renderOutputTab('HTML', $this->parsedResult, 'html'),
                        $this->renderOutputTab('Markdown', $this->markdownOutput, 'markdown'),
                        $this->renderOutputTab('纯文本', $this->textOutput, 'text')
                    )
            );

            $child->child(
                Element::make('div')
                    ->class('output-stats')
                    ->style('margin-top: 1rem; padding: 0.75rem; background: #ecfdf5; border-radius: 0.375rem; font-size: 0.875rem; color: #059669;')
                    ->text(sprintf(
                        '字数统计: %d 词, %d 字符 (含空格), %d 字符 (不含空格)',
                        DocumentParser::wordCount($this->extendedContent),
                        DocumentParser::characterCount($this->extendedContent, true),
                        DocumentParser::characterCount($this->extendedContent, false)
                    ))
            );
        } else {
            $child->child(
                Element::make('div')
                    ->style('padding: 2rem; text-align: center; color: #9ca3af;')
                    ->text('点击下方的"解析内容"按钮查看输出预览')
                    ->liveFragment('outputContent')
            );
        }

        return $section->child($child);
    }

    protected function renderOutputTab(string $title, string $content, string $format): Element
    {
        $bgColor = $format === 'html' ? '#dbeafe' : ($format === 'markdown' ? '#fef3c7' : '#f3f4f6');
        $borderColor = $format === 'html' ? '#3b82f6' : ($format === 'markdown' ? '#d97706' : '#6b7280');

        return Element::make('div')
            ->style("flex: 1; background: {$bgColor}; border: 1px solid {$borderColor}; border-radius: 0.375rem; padding: 1rem;")
            ->children(
                Element::make('h3')
                    ->style('font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem; color: #374151; text-transform: uppercase;')
                    ->text($title),
                Element::make('pre')
                    ->style('background: #fff; border: 1px solid #e5e7eb; border-radius: 0.25rem; padding: 0.75rem; font-size: 0.75rem; font-family: monospace; overflow-x: auto; max-height: 200px; overflow-y: auto; margin: 0; white-space: pre-wrap; word-break: break-word;')
                    ->text($content ?: '(空)')
            );
    }

    protected function renderActions(): Element
    {
        return Element::make('div')
            ->class('demo-actions')
            ->style('display: flex; gap: 1rem; justify-content: center; padding: 1rem;')
            ->children(
                Button::make()
                    ->label('🔍 解析内容')
                    ->variant('primary')
                    ->liveAction('parseContent')
                    ->attr('data-rich-live', 'true'),
                Button::make()
                    ->label('🗑️ 清空所有')
                    ->variant('danger')
                    ->outline()
                    ->liveAction('clearAll')
                    ->attr('data-rich-live', 'true')
            );
    }
}
