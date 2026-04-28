<?php

declare(strict_types=1);

namespace Framework\Admin\Live;

use Framework\Component\LiveComponent;
use Framework\Component\Attribute\LiveAction;
use Framework\Admin\AdminManager;
use Framework\Admin\Resource\ResourceInterface;
use Framework\UX\Form\FormBuilder;
use Framework\View\Base\Element;

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
        parent::toast($this->recordId ? '更新成功' : '创建成功');
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

    public function render(): string|Element
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
        $title = ($isEdit ? '编辑' : '创建') . $resource::getTitle();

        $headerEl = Element::make('div')->class('admin-form-header');
        $headerEl->child(Element::make('h1')->class('admin-form-title')->text($title));

        $prefix = AdminManager::getPrefix();
        $name = $resource::getName();
        $backUrl = "{$prefix}/{$name}";
        $headerEl->child(
            Element::make('a')
                ->class('admin-btn admin-btn-secondary admin-btn-sm')
                ->attr('href', $backUrl)
                ->attr('data-navigate', '')
                ->text('← 返回列表')
        );
        $wrapper->child($headerEl);

        if ($this->saved) {
            $wrapper->child(
                Element::make('div')->class('admin-form-success')->text($isEdit ? '更新成功！' : '创建成功！')
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
                ->text('保存')
        );
        $actionsEl->child(
            Element::make('button')
                ->class('admin-btn admin-btn-secondary')
                ->attr('type', 'button')
                ->liveAction('resetForm')
                ->text('重置')
        );
        $wrapper->child($actionsEl);

        return $wrapper;
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
                $errors[$name] = "{$label} 是必填项";
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
}
