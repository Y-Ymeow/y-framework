<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Extensions;

use Framework\UX\RichEditor\RichEditorExtension;
use Framework\View\Base\Element;

class MentionExtension extends RichEditorExtension
{
    protected string $trigger = '@';
    protected array $dataSource = [];
    protected ?\Closure $searchCallback = null;
    protected string $displayField = 'name';
    protected string $valueField = 'id';
    protected string $wrapperClass = 'mention-tag';

    protected function getDefaultConfig(): array
    {
        return [
            'trigger' => '@',
            'displayField' => 'name',
            'valueField' => 'id',
            'wrapperClass' => 'mention-tag',
            'maxResults' => 10,
        ];
    }

    protected function initialize(): void
    {
        $this->trigger = $this->config['trigger'];
        $this->displayField = $this->config['displayField'];
        $this->valueField = $this->config['valueField'];
        $this->wrapperClass = $this->config['wrapperClass'];
        $this->icon = '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
        $this->title = t('editor.mention');
    }

    public function getName(): string
    {
        return 'mention';
    }

    /**
     * 设置数据源
     * @param array $data 数据源数组
     * @return static
     */
    public function setDataSource(array $data): static
    {
        $this->dataSource = $data;
        return $this;
    }

    /**
     * 设置搜索回调（自定义搜索逻辑）
     * @param callable $callback 搜索回调
     * @return static
     */
    public function setSearchCallback(callable $callback): static
    {
        $this->searchCallback = $callback;
        return $this;
    }

    /**
     * 执行 Mention 插入
     * @param string $content 内容
     * @param array $params 参数（query, selected）
     * @return string 处理后的内容
     * @ux-example MentionExtension::execute($content, ['query' => '张三', 'selected' => ['id' => 1, 'name' => '张三']])
     */
    public function execute(string $content, array $params = []): string
    {
        $query = $params['query'] ?? '';
        $results = $this->search($query);

        if (empty($results)) {
            return $content;
        }

        $selected = $params['selected'] ?? $results[0];
        $mention = $this->renderMention($selected);

        return str_replace($this->trigger . $query, $mention, $content);
    }

    /**
     * 搜索匹配项
     * @param string $query 搜索关键词
     * @return array 匹配结果
     */
    protected function search(string $query): array
    {
        if ($this->searchCallback) {
            return ($this->searchCallback)($query);
        }

        return array_filter($this->dataSource, function ($item) use ($query) {
            $display = is_array($item) ? ($item[$this->displayField] ?? '') : (string)$item;
            return stripos($display, $query) !== false;
        });
    }

    /**
     * 渲染 Mention 标签
     * @param array $item 数据项
     * @return string HTML 标签
     */
    protected function renderMention(array $item): string
    {
        $display = $item[$this->displayField] ?? '';
        $value = $item[$this->valueField] ?? '';

        return sprintf(
            '<span class="%s" data-mention-value="%s">%s%s</span>',
            $this->wrapperClass,
            htmlspecialchars((string)$value),
            $this->trigger,
            htmlspecialchars($display)
        );
    }

    public function parse(string $content): string
    {
        $pattern = '/<span[^>]*class="[^"]*' . preg_quote($this->wrapperClass, '/') . '[^"]*"[^>]*data-mention-value="([^"]*)"[^>]*>([^<]*)<\/span>/i';

        return preg_replace_callback($pattern, function ($matches) {
            $value = $matches[1];
            $display = $matches[2];
            return sprintf('[%s:%s]', $this->trigger, $value);
        }, $content);
    }

    public function renderPreview(string $content): string
    {
        $pattern = '/\[' . preg_quote($this->trigger, '/') . ':([^\]]+)\]/';

        return preg_replace_callback($pattern, function ($matches) {
            $value = $matches[1];
            return sprintf(
                '<span class="%s" data-mention-value="%s">%s%s</span>',
                $this->wrapperClass,
                htmlspecialchars($value),
                $this->trigger,
                htmlspecialchars($value)
            );
        }, $content);
    }
}
