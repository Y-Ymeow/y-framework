<?php

declare(strict_types=1);

namespace Admin\Contracts\Live;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Admin\Services\AdminManager;
use Admin\Contracts\Resource\ResourceInterface;
use Admin\Contracts\Resource\BaseResource;
use Framework\UX\Form\FormBuilder;
use Framework\View\Base\Element;
use Framework\UX\UXComponent;

class AdminFormPage extends LiveComponent
{
    public string $resourceName = '';
    public ?int $recordId = null;
    public array $formData = [];
    public array $formErrors = [];
    public bool $saved = false;

    public function mount(): void
    {
        // 数据加载移到 render()，因为此时 recordId 可能还未设置
    }

    #[LiveAction]
    public function save(array $params = []): void
    {
        $resource = $this->getResource();
        if (!$resource) return;

        $this->formData = $params;
        $this->formErrors = $this->validateFormData($resource);

        if (!empty($this->formErrors)) {
            $this->refresh('admin-form');
            return;
        }

        $modelClass = $resource::getModel();

        if ($this->recordId) {
            $model = $modelClass::find($this->recordId);
            if ($model) {
                foreach ($this->formData as $key => $value) {
                    $model->$key = $value;
                }
                $model->save();
            }
        } else {
            $model = new $modelClass();
            foreach ($this->formData as $key => $value) {
                $model->$key = $value;
            }
            $model->save();
            $this->recordId = (int)$model->id;
        }

        $this->saved = true;
        parent::toast($this->recordId ? t('admin:actions.update_success', [], '更新成功') : t('admin:actions.create_success', [], '创建成功'));
        $this->refresh('admin-form');
    }

    #[LiveAction]
    public function resetForm(): void
    {
        $this->formErrors = [];
        $this->saved = false;
        if ($this->recordId) {
            $this->loadRecord();
        } else {
            $this->formData = [];
        }
        $this->refresh('admin-form');
    }

    public function render(): Element
    {
        // 编辑模式且未加载数据时，从数据库加载
        if ($this->recordId && empty($this->formData)) {
            $this->loadRecord();
        }

        $resource = $this->getResource();
        if (!$resource) {
            return Element::make('div')->class('admin-form-wrapper')->text('Resource not found');
        }

        $wrapper = Element::make('div')->class('admin-form-wrapper');
        $wrapper->liveFragment('admin-form');

        $isEdit = $this->recordId !== null;
        $titleData = $resource::getTitle();
        $titleKey = is_array($titleData) ? $titleData[0] : $titleData;
        $titleParams = is_array($titleData) ? ($titleData[1] ?? []) : [];
        $titleDefault = is_array($titleData) ? ($titleData[2] ?? '') : $titleData;

        $actionKey = $isEdit ? 'admin:actions.edit' : 'admin:actions.create';
        $actionDefault = $isEdit ? '编辑' : '创建';

        $headerEl = Element::make('div')->class('admin-form-header');
        $headerEl->child(
            Element::make('h1')->class('admin-form-title')->child(
                Element::make('span')->intl($actionKey, [], $actionDefault)
            )->child(
                Element::make('span')->intl($titleKey, $titleParams, $titleDefault)
            )
        );

        $prefix = AdminManager::getPrefix();
        $name = $resource::getName();
        $backUrl = "{$prefix}/{$name}";
        $headerEl->child(
            Element::make('a')
                ->class('admin-btn admin-btn-secondary admin-btn-sm')
                ->attr('href', $backUrl)
                ->attr('data-navigate', '')
                ->child(Element::make('span')->intl('admin:actions.back_to_list', [], '← 返回列表'))
        );
        $wrapper->child($headerEl);

        if ($resource instanceof BaseResource) {
            $this->renderFormLifecycle($wrapper, $resource, $isEdit);
        } else {
            $headerContent = $resource->getHeader();
            if ($headerContent !== null) {
                $wrapper->child($this->resolveContent($headerContent));
            }

            if ($this->saved) {
                $wrapper->child(
                    Element::make('div')->class('admin-form-success')->intl(
                        $isEdit ? 'admin:actions.update_success' : 'admin:actions.create_success',
                        [],
                        $isEdit ? '更新成功！' : '创建成功！'
                    )
                );
            }

            if (!empty($this->formErrors)) {
                $errorBox = Element::make('div')->class('admin-form-errors');
                foreach ($this->formErrors as $field => $message) {
                    $errorBox->child(
                        Element::make('div')->class('admin-form-error-item')->text("{$field}: {$message}")
                    );
                }
                $wrapper->child($errorBox);
            }

            $form = new FormBuilder();
            $form->id('admin-form');
            $resource->configureForm($form);
            $form->fill($this->formData);

            $wrapper->child($form->render());

            $actionsEl = Element::make('div')->class('admin-form-actions');
            $actionsEl->child(
                Element::make('button')
                    ->class('admin-btn admin-btn-primary')
                    ->attr('type', 'submit')
                    ->attr('form', 'admin-form')
                    ->liveAction('save', 'click')
                    ->child(Element::make('span')->intl('admin:actions.save', [], '保存'))
            );
            $actionsEl->child(
                Element::make('button')
                    ->class('admin-btn admin-btn-secondary')
                    ->attr('type', 'button')
                    ->liveAction('resetForm')
                    ->child(Element::make('span')->intl('admin:actions.reset', [], '重置'))
            );
            $wrapper->child($actionsEl);

            $footerContent = $resource->getFooter();
            if ($footerContent !== null) {
                $wrapper->child($this->resolveContent($footerContent));
            }
        }

        return $wrapper;
    }

    protected function renderFormLifecycle(Element $wrapper, BaseResource $resource, bool $isEdit): void
    {
        $record = $this->recordId ? $resource::getModel()::find($this->recordId) : null;
        $resource->setRecord($record);

        $beforeHeader = $resource->getFormBeforeHeader($isEdit, $record);
        if ($beforeHeader !== null) {
            $wrapper->child($this->resolveContent($beforeHeader));
        }

        $formHeader = $resource->getFormHeader($isEdit, $record) ?? $resource->getHeader();
        if ($formHeader !== null) {
            $wrapper->child($this->resolveContent($formHeader));
        }

        $afterHeader = $resource->getFormAfterHeader($isEdit, $record);
        if ($afterHeader !== null) {
            $wrapper->child($this->resolveContent($afterHeader));
        }

        if ($this->saved) {
            $wrapper->child(
                Element::make('div')->class('admin-form-success')->intl(
                    $isEdit ? 'admin:actions.update_success' : 'admin:actions.create_success',
                    [],
                    $isEdit ? '更新成功！' : '创建成功！'
                )
            );
        }

        if (!empty($this->formErrors)) {
            $errorBox = Element::make('div')->class('admin-form-errors');
            foreach ($this->formErrors as $field => $message) {
                $errorBox->child(
                    Element::make('div')->class('admin-form-error-item')->text("{$field}: {$message}")
                );
            }
            $wrapper->child($errorBox);
        }

        $beforeForm = $resource->getFormBeforeForm($isEdit, $record);
        if ($beforeForm !== null) {
            $wrapper->child($this->resolveContent($beforeForm));
        }

        $form = new FormBuilder();
        $form->id('admin-form');
        $resource->configureForm($form);
        $form->fill($this->formData);

        $wrapper->child($form->render());

        $afterForm = $resource->getFormAfterForm($isEdit, $record);
        if ($afterForm !== null) {
            $wrapper->child($this->resolveContent($afterForm));
        }

        $actionsEl = Element::make('div')->class('admin-form-actions');
        $actionsEl->child(
            Element::make('button')
                ->class('admin-btn admin-btn-primary')
                ->attr('type', 'submit')
                ->attr('form', 'admin-form')
                ->liveAction('save', 'click')
                ->child(Element::make('span')->intl('admin:actions.save', [], '保存'))
        );
        $actionsEl->child(
            Element::make('button')
                ->class('admin-btn admin-btn-secondary')
                ->attr('type', 'button')
                ->liveAction('resetForm')
                ->child(Element::make('span')->intl('admin:actions.reset', [], '重置'))
        );
        $wrapper->child($actionsEl);

        $beforeFooter = $resource->getFormBeforeFooter($isEdit, $record);
        if ($beforeFooter !== null) {
            $wrapper->child($this->resolveContent($beforeFooter));
        }

        $formFooter = $resource->getFormFooter($isEdit, $record) ?? $resource->getFooter();
        if ($formFooter !== null) {
            $wrapper->child($this->resolveContent($formFooter));
        }

        $afterFooter = $resource->getFormAfterFooter($isEdit, $record);
        if ($afterFooter !== null) {
            $wrapper->child($this->resolveContent($afterFooter));
        }
    }

    /**
     * 将 Resource getHeader/getFooter 的返回值统一解析为 Element
     */
    protected function resolveContent(mixed $content): mixed
    {
        if ($content instanceof Element || $content instanceof UXComponent || $content instanceof LiveComponent) {
            return $content;
        }
        return Element::make('div')->text((string)$content);
    }

    protected function loadRecord(): void
    {
        $resource = $this->getResource();
        if (!$resource) return;

        $modelClass = $resource::getModel();
        $record = $modelClass::find($this->recordId);
        if ($record) {
            $this->formData = $record->toArray();
        }
    }

    protected function validateFormData(ResourceInterface $resource): array
    {
        $errors = [];
        $form = new FormBuilder();
        $resource->configureForm($form);

        $ref = new \ReflectionClass($form);
        $prop = $ref->getProperty('fields');
        $prop->setAccessible(true);
        $fields = $prop->getValue($form);

        foreach ($fields as $field) {
            $name = $field['name'] ?? '';
            $label = $field['label'] ?? $name;
            $required = $field['required'] ?? false;

            if ($required && empty($this->formData[$name]) && $this->formData[$name] !== '0') {
                $errors[$name] = t('admin:validation.required', ['field' => $label], "{$label} 是必填项");
            }
        }

        return $errors;
    }

    protected function getResource(): ?ResourceInterface
    {
        $resourceClass = AdminManager::getResource($this->resourceName);
        if (!$resourceClass) return null;
        return new $resourceClass();
    }

    public static function resource(string $resourceName): \Closure
    {
        return function ($id = null) use ($resourceName) {
            $page = new static();
            $page->resourceName = $resourceName;

            if ($id !== null) {
                $page->recordId = (int)$id;
            }

            $page->named("admin-form-{$resourceName}-" . ($id ?: 'create'));

            $layout = new AdminLayout();
            $layout->activeMenu = $resourceName;
            $layout->setContent($page);

            return $layout;
        };
    }
}
