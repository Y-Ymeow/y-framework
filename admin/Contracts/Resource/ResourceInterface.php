<?php

namespace Admin\Contracts\Resource;

use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;

interface ResourceInterface
{
    public static function getName(): string;
    public static function getModel(): string;
    public static function getTitle(): string|array;
    /**
     * 获取资源定义的路由
     */
    public static function getRoutes(): array;

    public function configureForm(FormBuilder $form): void;
    public function configureTable(DataTable $table): void;

    /**
     * 列表页 / 表单页通用：渲染在内容区上方
     * 返回 Element|UXComponent|string|null
     */
    public function getHeader(): mixed;

    /**
     * 列表页 / 表单页通用：渲染在内容区下方
     * 返回 Element|UXComponent|string|null
     */
    public function getFooter(): mixed;

    /**
     * 手动注册 LiveActions
     * 格式: ['actionName' => 'methodName'] 或 ['actionName' => ['method' => 'methodName', 'event' => 'click']]
     */
    public function getLiveActions(): array;

    public static function getFormWidth(): string;
}
