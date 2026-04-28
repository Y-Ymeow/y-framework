<?php

namespace Framework\Admin\Resource;

use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\Database\Model;

interface ResourceInterface
{
    public static function getName(): string;
    public static function getModel(): string;
    public static function getTitle(): string;
    public static function getRoutePrefix(): ?string;

    public function configureForm(FormBuilder $form): void;
    public function configureTable(DataTable $table): void;

    /**
     * 手动注册 LiveActions
     * 格式: ['actionName' => 'methodName'] 或 ['actionName' => ['method' => 'methodName', 'event' => 'click']]
     */
    public function getLiveActions(): array;
}