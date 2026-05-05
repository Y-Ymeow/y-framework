<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

/**
 * 富文本编辑器 Trait
 *
 * Trait，为组件提供富文本编辑器的注册、扩展管理、内容解析和格式转换能力。
 *
 * @ux-category RichEditor
 * @ux-since 1.0.0
 * @ux-example
 * class MyComponent {
 *     use HasRichEditor;
 *     protected function boot() {
 *         $this->registerRichEditor('content');
 *         $this->addExtension('content', 'emoji', new EmojiExtension());
 *     }
 * }
 * @ux-example-end
 */
trait HasRichEditor
{
    protected array $richEditors = [];
    protected array $editorExtensions = [];

    /**
     * 注册富文本编辑器
     * @param string $name 编辑器名称
     * @param array $config 配置项
     * @return static
     * @ux-example $this->registerRichEditor('content', ['placeholder' => '请输入内容...'])
     * @ux-default toolbar=['bold','italic','underline','strike','|','heading','quote','code','|','list','link']
     * @ux-default minimal=false
     * @ux-default outputFormat='html'
     * @ux-default rows=10
     */
    protected function registerRichEditor(string $name, array $config = []): static
    {
        $this->richEditors[$name] = array_merge([
            'toolbar' => ['bold', 'italic', 'underline', 'strike', '|', 'heading', 'quote', 'code', '|', 'list', 'link'],
            'minimal' => false,
            'placeholder' => '',
            'outputFormat' => 'html',
            'rows' => 10,
        ], $config);

        return $this;
    }

    /**
     * 添加编辑器扩展
     * @param string $editorName 编辑器名称
     * @param string $extensionName 扩展名称
     * @param RichEditorExtension $extension 扩展实例
     * @return static
     * @ux-example $this->addExtension('content', 'emoji', new EmojiExtension())
     */
    protected function addExtension(string $editorName, string $extensionName, RichEditorExtension $extension): static
    {
        if (!isset($this->editorExtensions[$editorName])) {
            $this->editorExtensions[$editorName] = [];
        }

        $this->editorExtensions[$editorName][$extensionName] = $extension;
        ExtensionRegistry::register("{$editorName}.{$extensionName}", $extension);

        return $this;
    }

    /**
     * 获取编辑器配置
     * @param string $name 编辑器名称
     * @return array
     */
    protected function getEditorConfig(string $name): array
    {
        return $this->richEditors[$name] ?? [];
    }

    /**
     * 获取编辑器扩展列表
     * @param string $name 编辑器名称
     * @return array
     */
    protected function getEditorExtensions(string $name): array
    {
        return $this->editorExtensions[$name] ?? [];
    }

    /**
     * 解析编辑器内容（应用扩展和解析器）
     * @param string $content 内容
     * @param string $parser 解析器名称
     * @return string
     * @ux-example $this->parseEditorContent($content, 'markdown')
     */
    protected function parseEditorContent(string $content, string $parser = 'default'): string
    {
        return ExtensionRegistry::parseWith($content, $parser);
    }

    /**
     * 格式化编辑器内容
     * @param string $content 内容
     * @param string $format 格式（text/markdown/html）
     * @return string
     * @ux-example $this->formatEditorContent($content, 'markdown')
     */
    protected function formatEditorContent(string $content, string $format): string
    {
        return ExtensionRegistry::formatAs($content, $format);
    }

    /**
     * 清洗编辑器内容（移除危险标签和脚本）
     * @param string $content 内容
     * @return string
     * @ux-example $this->sanitizeEditorContent($html)
     */
    protected function sanitizeEditorContent(string $content): string
    {
        return DocumentParser::sanitize($content);
    }

    /**
     * 转换编辑器内容格式
     * @param string $content 内容
     * @param string $from 源格式（markdown/text/html）
     * @param string $to 目标格式（markdown/text/html）
     * @return string
     * @ux-example $this->convertEditorContent($content, 'markdown', 'html')
     */
    protected function convertEditorContent(string $content, string $from, string $to): string
    {
        $html = $content;

        if ($from === 'markdown') {
            $html = DocumentParser::markdownToHtml($content);
        }

        return match ($to) {
            'text' => DocumentParser::htmlToText($html),
            'markdown' => DocumentParser::htmlToMarkdown($html),
            default => $html,
        };
    }
}
