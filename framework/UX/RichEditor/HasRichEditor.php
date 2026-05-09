<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

/**
 * 富文本编辑器 Trait
 *
 * v2.0: 支持 Block 编辑器模式。
 * 新增 registerBlockEditor() 和 Block 相关操作方法。
 * 旧版 registerRichEditor() 保持兼容。
 *
 * @ux-category RichEditor
 * @ux-since 1.0.0
 * @ux-example
 * class MyComponent {
 *     use HasRichEditor;
 *     protected function boot() {
 *         $this->registerBlockEditor('content');
 *         $this->addBlockType('content', 'custom', BlockType::make('custom')
 *             ->title('自定义')
 *             ->category('common')
 *             ->attribute('text', ['type' => 'string', 'default' => ''])
 *             ->withRenderElement(function($attrs) {
 *                 return Element::make('div')->text($attrs['text'] ?? '');
 *             })
 *         );
 *     }
 * }
 */
trait HasRichEditor
{
    protected array $richEditors = [];
    protected array $editorExtensions = [];
    protected array $blockEditors = [];

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
     * 注册 Block 编辑器
     * @param string $name 编辑器名称
     * @param array $config 配置项
     *   - allowedBlocks: 允许的 block 类型（空数组=全部允许）
     *   - placeholder: 占位文本
     *   - maxBlocks: 最大 block 数量（0=不限）
     */
    protected function registerBlockEditor(string $name, array $config = []): static
    {
        $this->blockEditors[$name] = array_merge([
            'allowedBlocks' => [],
            'placeholder' => '',
            'maxBlocks' => 0,
        ], $config);

        BlockRegistry::registerCoreBlocks();

        return $this;
    }

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
     * 为 Block 编辑器添加自定义 Block 类型
     * @param string $editorName 编辑器名称
     * @param string $blockName Block 类型名
     * @param BlockType $blockType Block 类型定义
     */
    protected function addBlockType(string $editorName, string $blockName, BlockType $blockType): static
    {
        BlockRegistry::register($blockName, $blockType);

        if (!isset($this->blockEditors[$editorName])) {
            $this->blockEditors[$editorName] = [];
        }

        if (!isset($this->blockEditors[$editorName]['customBlocks'])) {
            $this->blockEditors[$editorName]['customBlocks'] = [];
        }

        $this->blockEditors[$editorName]['customBlocks'][] = $blockName;

        return $this;
    }

    /**
     * 为 Block 编辑器添加扩展（作为 Block 注册）
     */
    protected function addBlockExtension(string $editorName, string $extensionName, RichEditorExtension $extension): static
    {
        $extension->asBlock();
        $this->addExtension($editorName, $extensionName, $extension);

        return $this;
    }

    protected function getEditorConfig(string $name): array
    {
        return $this->richEditors[$name] ?? [];
    }

    protected function getEditorExtensions(string $name): array
    {
        return $this->editorExtensions[$name] ?? [];
    }

    /**
     * 获取 Block 编辑器配置
     */
    protected function getBlockEditorConfig(string $name): array
    {
        return $this->blockEditors[$name] ?? [];
    }

    /**
     * 获取 Block 编辑器可用的 Block 定义
     */
    protected function getBlockEditorDefinitions(string $name): array
    {
        $config = $this->getBlockEditorConfig($name);
        $allowedBlocks = $config['allowedBlocks'] ?? [];

        if (empty($allowedBlocks)) {
            return BlockRegistry::allDefinitions();
        }

        $definitions = [];
        foreach ($allowedBlocks as $blockName) {
            $blockType = BlockRegistry::get($blockName);
            if ($blockType) {
                $definitions[$blockName] = $blockType->toArray();
            }
        }

        return $definitions;
    }

    protected function parseEditorContent(string $content, string $parser = 'default'): string
    {
        return ExtensionRegistry::parseWith($content, $parser);
    }

    protected function formatEditorContent(string $content, string $format): string
    {
        return ExtensionRegistry::formatAs($content, $format);
    }

    protected function sanitizeEditorContent(string $content): string
    {
        return DocumentParser::sanitize($content);
    }

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

    /**
     * 解析 Block JSON 为 Block 数组
     */
    protected function parseBlockContent(string $json): array
    {
        return BlockRegistry::parse($json);
    }

    /**
     * 渲染 Block 数组为 HTML
     */
    protected function renderBlocks(array $blocks): string
    {
        return BlockRegistry::render($blocks);
    }

    /**
     * 序列化 Block 数组为 JSON
     */
    protected function serializeBlocks(array $blocks): string
    {
        return BlockRegistry::serialize($blocks);
    }

    /**
     * 将旧版 HTML 内容转换为 Block JSON
     */
    protected function htmlToBlockJson(string $html): string
    {
        $blocks = BlockRegistry::legacyHtmlToBlocks($html);
        return BlockRegistry::serialize($blocks);
    }
}
