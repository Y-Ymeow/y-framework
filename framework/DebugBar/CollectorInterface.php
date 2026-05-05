<?php

declare(strict_types=1);

namespace Framework\DebugBar;

interface CollectorInterface
{
    /**
     * 获取收集器的名称（唯一标识）
     */
    public function getName(): string;

    /**
     * 获取要在选项卡上显示的标签和图标数据
     * 
     * @return array ['label' => 'SQL', 'icon' => '🗃️', 'badge' => '5']
     */
    public function getTab(): array;

    /**
     * 获取收集到的具体数据内容
     */
    public function getData(): array;

    /**
     * 收集数据（通常在请求结束或响应创建时调用）
     */
    public function collect(): void;
}
