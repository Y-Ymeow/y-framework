<?php

declare(strict_types=1);

namespace Framework\Admin\Resource;

use Framework\Events\Hook;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\View\Base\Element;

abstract class BaseResource implements ResourceInterface
{
    protected ?object $record = null;

    /**
     * 生命周期阶段常量
     */
    public const LIFECYCLE_LIST_BEFORE_HEADER = 'resource.list.before_header';
    public const LIFECYCLE_LIST_AFTER_HEADER = 'resource.list.after_header';
    public const LIFECYCLE_LIST_BEFORE_TABLE = 'resource.list.before_table';
    public const LIFECYCLE_LIST_AFTER_TABLE = 'resource.list.after_table';
    public const LIFECYCLE_LIST_BEFORE_FOOTER = 'resource.list.before_footer';
    public const LIFECYCLE_LIST_AFTER_FOOTER = 'resource.list.after_footer';

    public const LIFECYCLE_FORM_BEFORE_HEADER = 'resource.form.before_header';
    public const LIFECYCLE_FORM_AFTER_HEADER = 'resource.form.after_header';
    public const LIFECYCLE_FORM_BEFORE_FORM = 'resource.form.before_form';
    public const LIFECYCLE_FORM_AFTER_FORM = 'resource.form.after_form';
    public const LIFECYCLE_FORM_BEFORE_FOOTER = 'resource.form.before_footer';
    public const LIFECYCLE_FORM_AFTER_FOOTER = 'resource.form.after_footer';

    public const LIFECYCLE_FORM_CREATING = 'resource.form.creating';
    public const LIFECYCLE_FORM_UPDATING = 'resource.form.updating';
    public const LIFECYCLE_FORM_CREATED = 'resource.form.created';
    public const LIFECYCLE_FORM_UPDATED = 'resource.form.updated';

    public const LIFECYCLE_TABLE_CONFIGURING = 'resource.table.configuring';
    public const LIFECYCLE_FORM_CONFIGURING = 'resource.form.configuring';

    public static function getName(): string
    {
        return static::resolveDefaultName();
    }

    public static function getModel(): string
    {
        return static::resolveDefaultModel();
    }

    public static function getTitle(): string
    {
        return static::resolveDefaultTitle();
    }

    public static function getRoutes(): array
    {
        $name = static::getName();
        return [
            "admin.resource.{$name}" => [
                'method' => 'GET',
                'path' => "/{$name}",
                'handler' => \Framework\Admin\Live\AdminListPage::resource($name),
            ],
            "admin.resource.{$name}.create" => [
                'method' => 'GET',
                'path' => "/{$name}/create",
                'handler' => \Framework\Admin\Live\AdminFormPage::resource($name),
            ],
            "admin.resource.{$name}.edit" => [
                'method' => 'GET',
                'path' => "/{$name}/{id}/edit",
                'handler' => \Framework\Admin\Live\AdminFormPage::resource($name),
            ],
        ];
    }

    /**
     * 为资源快捷创建自定义页面处理器（自动包裹 AdminLayout）
     * 
     * @param string $componentClass 组件类名
     * @param array $props 初始属性
     */
    public static function page(string $componentClass, array $props = []): \Closure
    {
        $resourceName = static::getName();
        return function (...$args) use ($componentClass, $props, $resourceName) {
            $page = app()->make($componentClass);

            // 注入属性
            foreach ($props as $key => $value) {
                if (property_exists($page, $key)) {
                    $page->$key = $value;
                }
            }

            // 如果组件有 named 方法，设置一个唯一的 ID
            if (method_exists($page, 'named')) {
                $shortName = strtolower((new \ReflectionClass($componentClass))->getShortName());
                $page->named("admin-res-{$resourceName}-{$shortName}");
            }

            $layout = new \Framework\Admin\Live\AdminLayout();
            $layout->activeMenu = $resourceName;
            $layout->setContent($page);

            return $layout;
        };
    }

    public function configureForm(FormBuilder $form): void
    {
        $this->fireLifecycle(self::LIFECYCLE_FORM_CONFIGURING, ['form' => $form]);
    }

    public function configureTable(DataTable $table): void
    {
        $this->fireLifecycle(self::LIFECYCLE_TABLE_CONFIGURING, ['table' => $table]);
    }

    public function getHeader(): mixed
    {
        return null;
    }

    public function getFooter(): mixed
    {
        return null;
    }

    public function getLiveActions(): array
    {
        return [];
    }

    public function getListHeader(): mixed
    {
        return null;
    }

    public function getListFooter(): mixed
    {
        return null;
    }

    public function getFormHeader(bool $isEdit, ?object $record = null): mixed
    {
        return null;
    }

    public function getFormFooter(bool $isEdit, ?object $record = null): mixed
    {
        return null;
    }

    public function getFormBeforeHeader(bool $isEdit, ?object $record = null): mixed
    {
        $result = $this->fireLifecycle(self::LIFECYCLE_FORM_BEFORE_HEADER, ['isEdit' => $isEdit, 'record' => $record]);
        return $result ?: null;
    }

    public function getFormAfterHeader(bool $isEdit, ?object $record = null): mixed
    {
        $result = $this->fireLifecycle(self::LIFECYCLE_FORM_AFTER_HEADER, ['isEdit' => $isEdit, 'record' => $record]);
        return $result ?: null;
    }

    public function getFormBeforeForm(bool $isEdit, ?object $record = null): mixed
    {
        $result = $this->fireLifecycle(self::LIFECYCLE_FORM_BEFORE_FORM, ['isEdit' => $isEdit, 'record' => $record]);
        return $result ?: null;
    }

    public function getFormAfterForm(bool $isEdit, ?object $record = null): mixed
    {
        $result = $this->fireLifecycle(self::LIFECYCLE_FORM_AFTER_FORM, ['isEdit' => $isEdit, 'record' => $record]);
        return $result ?: null;
    }

    public function getFormBeforeFooter(bool $isEdit, ?object $record = null): mixed
    {
        $result = $this->fireLifecycle(self::LIFECYCLE_FORM_BEFORE_FOOTER, ['isEdit' => $isEdit, 'record' => $record]);
        return $result ?: null;
    }

    public function getFormAfterFooter(bool $isEdit, ?object $record = null): mixed
    {
        $result = $this->fireLifecycle(self::LIFECYCLE_FORM_AFTER_FOOTER, ['isEdit' => $isEdit, 'record' => $record]);
        return $result ?: null;
    }

    public function getListBeforeHeader(): mixed
    {
        return $this->fireLifecycle(self::LIFECYCLE_LIST_BEFORE_HEADER);
    }

    public function getListAfterHeader(): mixed
    {
        return $this->fireLifecycle(self::LIFECYCLE_LIST_AFTER_HEADER);
    }

    public function getListBeforeTable(): mixed
    {
        return $this->fireLifecycle(self::LIFECYCLE_LIST_BEFORE_TABLE);
    }

    public function getListAfterTable(): mixed
    {
        return $this->fireLifecycle(self::LIFECYCLE_LIST_AFTER_TABLE);
    }

    public function getListBeforeFooter(): mixed
    {
        return $this->fireLifecycle(self::LIFECYCLE_LIST_BEFORE_FOOTER);
    }

    public function getListAfterFooter(): mixed
    {
        return $this->fireLifecycle(self::LIFECYCLE_LIST_AFTER_FOOTER);
    }

    public function onFormCreating(object $record): mixed
    {
        return $this->fireLifecycle(self::LIFECYCLE_FORM_CREATING, ['record' => $record]);
    }

    public function onFormUpdating(object $record): mixed
    {
        return $this->fireLifecycle(self::LIFECYCLE_FORM_UPDATING, ['record' => $record]);
    }

    public function onFormCreated(object $record): mixed
    {
        return $this->fireLifecycle(self::LIFECYCLE_FORM_CREATED, ['record' => $record]);
    }

    public function onFormUpdated(object $record): mixed
    {
        return $this->fireLifecycle(self::LIFECYCLE_FORM_UPDATED, ['record' => $record]);
    }

    protected function fireLifecycle(string $hook, array $context = []): mixed
    {
        $resourceName = static::getName();
        $fullHook = "{$hook}:{$resourceName}";

        Hook::fire($hook, $this, $context);
        Hook::fire($fullHook, $this, $context);

        return Hook::applyFilter($fullHook, Hook::applyFilter($hook, null, $this, $context), $this, $context);
    }

    public function setRecord(?object $record): void
    {
        $this->record = $record;
    }

    public function getRecord(): ?object
    {
        return $this->record;
    }

    public function fireLifecycleWithReturn(string $hook, array $context = []): mixed
    {
        $resourceName = static::getName();
        $fullHook = "{$hook}:{$resourceName}";

        $result1 = Hook::applyFilter($hook, null, $this, $context);
        $result2 = Hook::applyFilter($fullHook, $result1, $this, $context);

        return $result2;
    }

    protected static function resolveDefaultName(): string
    {
        $class = static::class;
        $parts = explode('\\', $class);
        $className = end($parts);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Resource', '', $className)));
    }

    protected static function resolveDefaultModel(): string
    {
        $reflection = new \ReflectionClass(static::class);
        $attrs = $reflection->getAttributes(\Framework\Admin\Attribute\AdminResource::class);

        if (!empty($attrs)) {
            $args = $attrs[0]->getArguments();
            return $args['model'] ?? $args[1] ?? '';
        }

        return '';
    }

    protected static function resolveDefaultTitle(): string
    {
        $reflection = new \ReflectionClass(static::class);
        $attrs = $reflection->getAttributes(\Framework\Admin\Attribute\AdminResource::class);

        if (!empty($attrs)) {
            $args = $attrs[0]->getArguments();
            return $args['title'] ?? $args[2] ?? static::resolveDefaultName();
        }

        return static::resolveDefaultName();
    }
}
