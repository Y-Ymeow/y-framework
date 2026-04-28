<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

trait HasRichEditor
{
    protected array $richEditors = [];
    protected array $editorExtensions = [];

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

    protected function addExtension(string $editorName, string $extensionName, RichEditorExtension $extension): static
    {
        if (!isset($this->editorExtensions[$editorName])) {
            $this->editorExtensions[$editorName] = [];
        }

        $this->editorExtensions[$editorName][$extensionName] = $extension;
        ExtensionRegistry::register("{$editorName}.{$extensionName}", $extension);

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
}
