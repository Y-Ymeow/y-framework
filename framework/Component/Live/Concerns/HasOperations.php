<?php

declare(strict_types=1);

namespace Framework\Component\Live\Concerns;

use Framework\View\Document\AssetRegistry;

/**
 * @mixin \Framework\Component\Live\LiveComponent
 */
trait HasOperations
{
    private array $operations = [];

    /**
     * 添加操作到队列（自动去重）
     */
    public function operation(string $op, array $params = []): void
    {
        $newOp = array_merge(['op' => $op], $params);
        foreach ($this->operations as $existing) {
            if ($existing === $newOp) return;
        }

        $this->operations[] = $newOp;
    }

    /**
     * 获取所有操作
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * 页面重定向
     */
    public function redirect(string $url): void
    {
        $this->operation('redirect', ['url' => $url]);
    }

    /**
     * 无刷新导航
     */
    public function navigateTo(string $url, ?string $fragment = null, bool $replace = false): void
    {
        $params = ['url' => $url];
        if ($fragment !== null) {
            $params['fragment'] = $fragment;
        }
        if ($replace) {
            $params['replace'] = true;
        }
        $this->operation('navigate', $params);
    }

    /**
     * 刷新页面
     */
    public function refreshPage(): void
    {
        $this->operation('reload');
    }

    /**
     * 派发前端事件
     */
    public function dispatchEvent(string $event, array $detail = []): void
    {
        $this->operation('dispatch', ['event' => $event, 'detail' => $detail]);
    }

    /**
     * 加载外部 JS 资源。
     */
    public function loadScript(string $src, ?string $id = null): void
    {
        if ($src === '') {
            return;
        }

        $this->operation('loadScript', [
            'src' => $src,
            'id' => $id ?? md5($src),
        ]);
    }

    /**
     * 通过 /_js?ids=... 加载 AssetRegistry 中已注册的 JS 片段。
     */
    public function loadScriptIds(string|array $ids): void
    {
        $ids = is_array($ids) ? $ids : [$ids];
        $src = AssetRegistry::getInstance()->buildScriptUrl($ids);

        if ($src === '') {
            return;
        }

        $this->loadScript($src, 'live:' . md5(implode(',', $ids)));
    }

    /**
     * UX 组件操作
     */
    public function ux(string $component, string $id, string $action, array $data = []): void
    {
        $this->operation('ux:' . $component, array_merge(['id' => $id, 'action' => $action], $data));
    }

    public function openModal(string $id): void
    {
        $this->ux('modal', $id, 'open');
    }

    public function closeModal(string $id): void
    {
        $this->ux('modal', $id, 'close');
    }

    public function toggleAccordion(string $itemId, ?bool $open = null): void
    {
        $this->ux('accordion', $itemId, 'toggle', ['open' => $open]);
    }

    public function toast(string $message, string $type = 'success', int $duration = 3000, ?string $title = null): void
    {
        $this->ux('toast', '', 'show', [
            'message' => $message,
            'type' => $type,
            'duration' => $duration,
            'title' => $title,
        ]);
    }

    public function confirm(string $message, string $title = '确认', array $options = []): void
    {
        $this->operation('confirm', [
            'message' => $message,
            'title' => $title,
            ...$options
        ]);
    }

    public function loading(string $target = ''): void
    {
        $this->operation('loading', ['target' => $target ?: 'self']);
    }

    public function loadingEnd(string $target = ''): void
    {
        $this->operation('loading:end', ['target' => $target ?: 'self']);
    }

    /**
     * 替换组件完整 HTML
     */
    public function replace(): void
    {
        $this->operation('replace');
    }

    /**
     * 替换指定 CSS 选择器的元素
     */
    public function replaceElement(string $selector, ?string $content = null): void
    {
        $this->operation('replaceElement', [
            'selector' => $selector,
            'content' => $content,
        ]);
    }

    /**
     * 添加子元素到指定位置
     */
    public function addChild(string $selector, string $content, string $position = 'beforeend'): void
    {
        $this->operation('addChild', [
            'selector' => $selector,
            'content' => $content,
            'position' => $position,
        ]);
    }

    /**
     * 更新指定选择器元素的属性值
     */
    public function updateAttribute(string $selector, string $attribute, string $value): void
    {
        $this->operation('updateAttribute', [
            'selector' => $selector,
            'attribute' => $attribute,
            'value' => $value,
        ]);
    }

    /**
     * 手动触发另一个组件的方法
     * 格式: componentId.actionName
     */
    public function trigger(string $targetAction, array $params = []): void
    {
        $this->operation('trigger', [
            'target' => $targetAction,
            'params' => $params,
        ]);
    }

    /**
     * 组件操作返回
     */
    public function handleOperation(): array
    {
        return [
            'op' => $this->getOperations(),
        ];
    }
}
