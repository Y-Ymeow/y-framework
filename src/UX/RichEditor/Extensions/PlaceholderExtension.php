<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Extensions;

use Framework\UX\RichEditor\RichEditorExtension;

class PlaceholderExtension extends RichEditorExtension
{
    protected array $placeholders = [];
    protected string $wrapperClass = 'placeholder-tag';
    protected string $pattern = '/\{\{([^}]+)\}\}/';

    protected function getDefaultConfig(): array
    {
        return [
            'wrapperClass' => 'placeholder-tag',
            'pattern' => '/\{\{([^}]+)\}\}/',
        ];
    }

    protected function initialize(): void
    {
        $this->wrapperClass = $this->config['wrapperClass'];
        $this->pattern = $this->config['pattern'];
        $this->icon = '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 10h2v7H7zm4-3h2v10h-2zm4 6h2v4h-2z"/></svg>';
        $this->title = t('editor.placeholder');
    }

    public function getName(): string
    {
        return 'placeholder';
    }

    public function setPlaceholders(array $placeholders): static
    {
        $this->placeholders = $placeholders;
        return $this;
    }

    /**
     * 添加占位符
     * @param string $key 占位符键
     * @param string $label 显示标签
     * @param mixed $defaultValue 默认值
     * @return static
     * @ux-example $this->addPlaceholder('username', '用户名', '匿名用户')
     */
    public function addPlaceholder(string $key, string $label, mixed $defaultValue = null): static
    {
        $this->placeholders[$key] = [
            'label' => $label,
            'default' => $defaultValue,
        ];
        return $this;
    }

    /**
     * 执行占位符插入
     * @param string $content 内容
     * @param array $params 参数（key）
     * @return string 处理后的内容
     */
    public function execute(string $content, array $params = []): string
    {
        $key = $params['key'] ?? '';
        if (!isset($this->placeholders[$key])) {
            return $content;
        }

        $placeholder = $this->renderPlaceholder($key);
        return $content . $placeholder;
    }

    /**
     * 渲染占位符标签
     * @param string $key 占位符键
     * @return string HTML 标签
     */
    protected function renderPlaceholder(string $key): string
    {
        $label = $this->placeholders[$key]['label'] ?? $key;

        return sprintf(
            '<span class="%s" data-placeholder-key="%s" contenteditable="false">{{%s}}</span>&nbsp;',
            $this->wrapperClass,
            htmlspecialchars($key),
            htmlspecialchars($label)
        );
    }

    /**
     * 解析占位符标签为短代码
     * @param string $content 内容
     * @return string 解析后的内容
     */
    public function parse(string $content): string
    {
        return preg_replace_callback(
            '/<span[^>]*class="[^"]*' . preg_quote($this->wrapperClass, '/') . '[^"]*"[^>]*data-placeholder-key="([^"]*)"[^>]*>[^<]*<\/span>/i',
            function ($matches) {
                $key = $matches[1];
                return '{{' . $key . '}}';
            },
            $content
        );
    }

    /**
     * 渲染预览内容（将短代码转为占位符标签）
     * @param string $content 内容
     * @return string 预览内容
     */
    public function renderPreview(string $content): string
    {
        return preg_replace_callback($this->pattern, function ($matches) {
            $key = trim($matches[1]);
            $label = $this->placeholders[$key]['label'] ?? $key;

            return sprintf(
                '<span class="%s" data-placeholder-key="%s">%s</span>',
                $this->wrapperClass,
                htmlspecialchars($key),
                htmlspecialchars($label)
            );
        }, $content);
    }

    /**
     * 在内容中替换占位符为实际值
     * @param string $content 内容
     * @param array $values 值映射
     * @return string 替换后的内容
     * @ux-example $this->replaceInContent($content, ['username' => '张三', 'date' => '2024-01-01'])
     */
    public function replaceInContent(string $content, array $values): string
    {
        return preg_replace_callback($this->pattern, function ($matches) use ($values) {
            $key = trim($matches[1]);
            $default = $this->placeholders[$key]['default'] ?? '';
            return (string)($values[$key] ?? $default);
        }, $content);
    }

    /**
     * 获取所有占位符
     * @return array 占位符列表
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }
}
