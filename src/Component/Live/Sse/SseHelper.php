<?php

declare(strict_types=1);

namespace Framework\Component\Live\Sse;

use Framework\Http\Session;
use Framework\View\Base\Element;
use Framework\UX\UXComponent;

/**
 * SSE 视图助手 — 在页面中注入 SSE 配置
 *
 * ## 使用方式
 *
 * ### 1. 在 Document 中注入配置
 *
 * ```php
 * $document = Document::make('页面标题')
 *     ->main($content)
 *     ->inject('head', SseHelper::metaElement(['notifications', 'orders']));
 * ```
 *
 * ### 2. 在 UX 组件上添加订阅
 *
 * ```php
 * $dashboard = Container::make()
 *     ->id('dashboard')
 *     ->dataLiveSse('dashboard', 'notifications'); // 订阅频道
 * ```
 *
 * @since 2.0
 */
class SseHelper
{
    /**
     * 生成 SSE 配置 meta Element
     *
     * @param array $channels 默认订阅的频道
     * @return Element
     */
    public static function metaElement(array $channels = []): Element
    {
        $session = new Session();

        if (!empty($channels)) {
            $session->set('sse_channels', $channels);
        }

        $token = SseToken::generate($channels);

        // 关闭 Session 释放锁（关键！）
        $session->close();

        $config = json_encode([
            'token' => $token->toString(),
            'endpoint' => '/live/sse/' . $token->toString(),
            'channels' => $channels,
        ], JSON_UNESCAPED_UNICODE);

        return Element::make('meta')
            ->attr('name', 'sse-config')
            ->attr('content', $config);
    }

    /**
     * 在 Element 上添加 SSE 订阅
     *
     * @param Element $element 目标元素
     * @param string ...$channels 频道列表
     * @return Element
     */
    public static function subscribe(Element $element, string ...$channels): Element
    {
        return $element->data('live-sse', implode(',', $channels));
    }

    /**
     * 获取 SSE Token（用于 API 响应）
     *
     * @param array $channels 允许的频道
     * @return array
     */
    public static function tokenConfig(array $channels = []): array
    {
        $token = SseToken::generate($channels);

        return [
            'token' => $token->toString(),
            'endpoint' => '/live/sse/' . $token->toString(),
            'channels' => $channels,
        ];
    }
}
