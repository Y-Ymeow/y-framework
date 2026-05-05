<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UI\Button;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 表单构建器
 *
 * 用于快速构建表单，支持多种字段类型、HTTP 方法、提交按钮、Live 绑定、数据填充。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example FormBuilder::make()->post()->action('/save')->text('name', '姓名')->email('email', '邮箱')->submitLabel('提交')
 * @ux-example FormBuilder::make()->get()->text('q', '搜索')->submitLabel('搜索')
 * @ux-js-component form-builder.js
 * @ux-css form.css
 */
class FormBuilder extends UXComponent
{
    protected string $method = 'POST';
    protected string $action = '';
    protected array $fields = [];
    protected bool $multipart = false;
    protected ?string $submitLabel = null;
    protected array $liveBind = [];
    protected int $columns = 1;
    protected ?string $currentSection = null;
    protected array $sections = [];

    /**
     * 设置表单提交方法
     * @param string $method HTTP 方法：GET/POST/PUT/DELETE
     * @return static
     * @ux-example FormBuilder::make()->method('POST')
     * @ux-default 'POST'
     */
    public function method(string $method): static
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * 设置为 GET 方法
     * @return static
     * @ux-example FormBuilder::make()->get()
     */
    public function get(): static
    {
        return $this->method('GET');
    }

    /**
     * 设置为 POST 方法
     * @return static
     * @ux-example FormBuilder::make()->post()
     */
    public function post(): static
    {
        return $this->method('POST');
    }

    /**
     * 设置为 PUT 方法
     * @return static
     * @ux-example FormBuilder::make()->put()
     */
    public function put(): static
    {
        return $this->method('PUT');
    }

    /**
     * 设置为 DELETE 方法
     * @return static
     * @ux-example FormBuilder::make()->delete()
     */
    public function delete(): static
    {
        return $this->method('DELETE');
    }

    /**
     * 设置表单提交地址
     * @param string $action 提交 URL
     * @return static
     * @ux-example FormBuilder::make()->action('/save')
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 启用 multipart（用于文件上传）
     * @param bool $multipart 是否启用
     * @return static
     * @ux-example FormBuilder::make()->multipart()
     * @ux-default true
     */
    public function multipart(bool $multipart = true): static
    {
        $this->multipart = $multipart;
        return $this;
    }

    /**
     * 设置提交按钮文字
     * @param string $label 按钮文字
     * @return static
     * @ux-example FormBuilder::make()->submitLabel('提交')
     */
    public function submitLabel(string $label): static
    {
        $this->submitLabel = $label;
        return $this;
    }

    /**
     * 添加文本输入框字段
     * @param string $name 字段名
     * @param string $label 标签文字
     * @param array $options 选项（placeholder, required, disabled 等）
     * @return static
     * @ux-example FormBuilder::make()->text('name', '姓名')
     */
    public function text(string $name, string $label, array $options = []): static
    {
        $this->addField(array_merge([
            'type' => 'text',
            'name' => $name,
            'label' => $label,
        ], $options));
        return $this;
    }

    /**
     * 添加邮箱输入框字段
     * @param string $name 字段名
     * @param string $label 标签文字
     * @param array $options 选项
     * @return static
     * @ux-example FormBuilder::make()->email('email', '邮箱')
     */
    public function email(string $name, string $label, array $options = []): static
    {
        $this->addField(array_merge([
            'type' => 'email',
            'name' => $name,
            'label' => $label,
        ], $options));
        return $this;
    }

    /**
     * 添加密码输入框字段
     * @param string $name 字段名
     * @param string $label 标签文字
     * @param array $options 选项
     * @return static
     * @ux-example FormBuilder::make()->password('password', '密码')
     */
    public function password(string $name, string $label, array $options = []): static
    {
        $this->addField(array_merge([
            'type' => 'password',
            'name' => $name,
            'label' => $label,
        ], $options));
        return $this;
    }

    /**
     * 添加数字输入框字段
     * @param string $name 字段名
     * @param string $label 标签文字
     * @param array $options 选项
     * @return static
     * @ux-example FormBuilder::make()->number('age', '年龄')
     */
    public function number(string $name, string $label, array $options = []): static
    {
        $this->addField(array_merge([
            'type' => 'number',
            'name' => $name,
            'label' => $label,
        ], $options));
        return $this;
    }

    /**
     * 添加多行文本框字段
     * @param string $name 字段名
     * @param string $label 标签文字
     * @param array $options 选项
     * @return static
     * @ux-example FormBuilder::make()->textarea('content', '内容')
     */
    public function textarea(string $name, string $label, array $options = []): static
    {
        $this->addField(array_merge([
            'type' => 'textarea',
            'name' => $name,
            'label' => $label,
        ], $options));
        return $this;
    }

    /**
     * 添加富文本编辑器字段
     * @param string $name 字段名
     * @param string $label 标签文字
     * @param array $options 选项
     * @return static
     * @ux-example FormBuilder::make()->richEditor('content', '内容')
     */
    public function richEditor(string $name, string $label, array $options = []): static
    {
        $this->addField(array_merge([
            'type' => 'richEditor',
            'name' => $name,
            'label' => $label,
        ], $options));
        return $this;
    }

    /**
     * 添加下拉选择框字段
     * @param string $name 字段名
     * @param string $label 标签文字
     * @param array $options 字段选项
     * @param array $selectOptions 下拉选项 ['value' => 'label']
     * @return static
     * @ux-example FormBuilder::make()->select('city', '城市', [], ['Beijing' => '北京'])
     */
    public function select(string $name, string $label, array $options = [], array $selectOptions = []): static
    {
        $this->addField(array_merge([
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'options' => $selectOptions,
        ], $options));
        return $this;
    }

    /**
     * 添加复选框字段
     * @param string $name 字段名
     * @param string $label 标签文字
     * @param array $options 选项
     * @return static
     * @ux-example FormBuilder::make()->checkbox('agree', '同意协议')
     */
    public function checkbox(string $name, string $label, array $options = []): static
    {
        $this->addField(array_merge([
            'type' => 'checkbox',
            'name' => $name,
            'label' => $label,
        ], $options));
        return $this;
    }

    /**
     * 添加单选框字段
     * @param string $name 字段名
     * @param string $label 标签文字
     * @param array $choices 选项列表 ['value' => 'label']
     * @param array $options 选项
     * @return static
     * @ux-example FormBuilder::make()->radio('gender', '性别', ['male' => '男', 'female' => '女'])
     */
    public function radio(string $name, string $label, array $choices, array $options = []): static
    {
        $this->addField(array_merge([
            'type' => 'radio',
            'name' => $name,
            'label' => $label,
            'choices' => $choices,
        ], $options));
        return $this;
    }

    /**
     * 添加文件上传字段
     * @param string $name 字段名
     * @param string $label 标签文字
     * @param array $options 选项
     * @return static
     * @ux-example FormBuilder::make()->file('avatar', '头像')
     */
    public function file(string $name, string $label, array $options = []): static
    {
        $this->multipart = true;
        $this->addField(array_merge([
            'type' => 'file',
            'name' => $name,
            'label' => $label,
        ], $options));
        return $this;
    }

    /**
     * 添加隐藏字段
     * @param string $name 字段名
     * @param string $value 默认值
     * @return static
     * @ux-example FormBuilder::make()->hidden('csrf_token', $token)
     */
    public function hidden(string $name, string $value): static
    {
        $this->addField([
            'type' => 'hidden',
            'name' => $name,
            'value' => $value,
        ]);
        return $this;
    }

    /**
     * 绑定字段到 LiveComponent 属性
     * @param string $field 字段名
     * @param string $property LiveComponent 属性名
     * @return static
     * @ux-example FormBuilder::make()->liveBind('email', 'user.email')
     */
    public function liveBind(string $field, string $property): static
    {
        $this->liveBind[$field] = $property;
        return $this;
    }

    public function columns(int $cols): static
    {
        $this->columns = max(1, min(4, $cols));
        return $this;
    }

    public function section(string $title, ?string $description = null): static
    {
        $this->currentSection = $title;
        $this->sections[$title] = [
            'title' => $title,
            'description' => $description,
            'fields' => [],
        ];
        return $this;
    }

    protected function addField(array $field): void
    {
        $field['_section'] = $this->currentSection;
        $this->fields[] = $field;

        if ($this->currentSection !== null && isset($this->sections[$this->currentSection])) {
            $this->sections[$this->currentSection]['fields'][] = $field;
        }
    }

    /**
     * 填充表单数据
     * @param array $data 数据数组
     * @return static
     * @ux-example FormBuilder::make()->fill(['name' => '张三', 'email' => 'test@example.com'])
     */
    public function fill(array $data): static
    {
        foreach ($this->fields as &$field) {
            $name = $field['name'] ?? '';
            if ($name && isset($data[$name])) {
                $field['value'] = $data[$name];
            }
        }
        return $this;
    }

    protected function toElement(): Element
    {
        $formEl = new Element('form');
        $this->buildElement($formEl);

        $formEl->attr('method', $this->method);
        if ($this->action) {
            $formEl->attr('action', $this->action);
        }
        if ($this->multipart) {
            $formEl->attr('enctype', 'multipart/form-data');
        }

        if (!empty($this->sections)) {
            foreach ($this->sections as $section) {
                $sectionEl = Element::make('div')->class('ux-form-section', 'mb-8');

                $headerEl = Element::make('div')->class('ux-form-section-header', 'mb-4', 'pb-3', 'border-b', 'border-gray-200');
                $headerEl->child(
                    Element::make('h3')->class('text-lg', 'font-semibold', 'text-gray-900')->text($section['title'])
                );
                if ($section['description']) {
                    $headerEl->child(
                        Element::make('p')->class('text-sm', 'text-gray-500', 'mt-1')->text($section['description'])
                    );
                }
                $sectionEl->child($headerEl);

                $gridEl = $this->buildGrid();
                foreach ($section['fields'] as $field) {
                    $gridEl->child($this->renderField($field));
                }
                $sectionEl->child($gridEl);

                $formEl->child($sectionEl);
            }

            $orphanFields = array_filter($this->fields, function ($f) {
                return !isset($f['_section']) || $f['_section'] === null;
            });
            if (!empty($orphanFields)) {
                $gridEl = $this->buildGrid();
                foreach ($orphanFields as $field) {
                    $gridEl->child($this->renderField($field));
                }
                $formEl->child($gridEl);
            }
        } else {
            $gridEl = $this->buildGrid();
            foreach ($this->fields as $field) {
                $gridEl->child($this->renderField($field));
            }
            $formEl->child($gridEl);
        }

        if ($this->submitLabel) {
            $btnGroupEl = Element::make('div')->class('ux-form-group', 'pt-4', 'border-t', 'border-gray-200');
            $btnGroupEl->child(
                Button::make()
                    ->label($this->submitLabel)
                    ->submit()
                    ->primary()
            );
            $formEl->child($btnGroupEl);
        }

        return $formEl;
    }

    protected function buildGrid(): Element
    {
        $gridEl = Element::make('div');
        if ($this->columns > 1) {
            $gridEl->class('grid', 'gap-4');
            $colsMap = [2 => 'grid-cols-2', 3 => 'grid-cols-3', 4 => 'grid-cols-4'];
            $gridEl->class($colsMap[$this->columns] ?? 'grid-cols-2');
        } else {
            $gridEl->class('space-y-0');
        }
        return $gridEl;
    }

    private function renderField(array $field): Element
    {
        $type = $field['type'];

        if ($type === 'hidden') {
            return Element::make('input')
                ->attr('type', 'hidden')
                ->attr('name', $field['name'])
                ->attr('value', $field['value']);
        }

        $groupEl = Element::make('div')->class('ux-form-group');

        $name = $field['name'] ?? '';
        $label = $field['label'] ?? '';

        if ($label && $type !== 'checkbox') {
            $labelEl = Element::make('label')
                ->class('ux-form-label')
                ->attr('for', $name)
                ->text($label);

            if ($field['required'] ?? false) {
                $labelEl->child(Element::make('span')->class('ux-form-required')->text('*'));
            }

            $groupEl->child($labelEl);
        }

        $inputAttrs = $this->buildFieldAttrs($field);

        switch ($type) {
            case 'textarea':
                $textareaEl = Element::make('textarea');
                foreach ($inputAttrs as $key => $value) {
                    $textareaEl->attr($key, $value);
                }
                $textareaEl->text($field['value'] ?? '');
                $groupEl->child($textareaEl);
                break;

            case 'richEditor':
                $richEditor = new RichEditor();
                $richEditor->name($name)->label($label);

                if (isset($field['value'])) {
                    $richEditor->value($field['value']);
                }
                if (isset($field['placeholder'])) {
                    $richEditor->placeholder($field['placeholder']);
                }
                if (isset($field['required']) && $field['required']) {
                    $richEditor->required(true);
                }
                if (isset($field['disabled']) && $field['disabled']) {
                    $richEditor->disabled(true);
                }
                if (isset($field['toolbar'])) {
                    $richEditor->toolbar($field['toolbar']);
                }
                if (isset($field['minimal'])) {
                    $richEditor->minimal($field['minimal']);
                }
                if (isset($field['rows'])) {
                    $richEditor->rows($field['rows']);
                }
                if (isset($field['outputFormat'])) {
                    $richEditor->outputFormat($field['outputFormat']);
                }
                if (isset($field['help'])) {
                    $richEditor->help($field['help']);
                }

                $groupEl->child($richEditor->render());
                break;

            case 'select':
                $selectEl = Element::make('select');
                foreach ($inputAttrs as $key => $value) {
                    $selectEl->attr($key, $value);
                }
                foreach ($field['options'] as $value => $labelText) {
                    $optionEl = Element::make('option')
                        ->attr('value', (string)$value)
                        ->text($labelText);
                    if (($field['value'] ?? '') === (string)$value) {
                        $optionEl->attr('selected', '');
                    }
                    $selectEl->child($optionEl);
                }
                $groupEl->child($selectEl);
                break;

            case 'checkbox':
                $checked = ($field['checked'] ?? false) ? 'checked' : '';
                $checkboxLabelEl = Element::make('label')->class('ux-form-checkbox');
                $checkboxInputEl = Element::make('input')
                    ->attr('type', 'checkbox')
                    ->attr('name', $name)
                    ->attr('value', '1');
                foreach ($inputAttrs as $key => $value) {
                    if ($key !== 'name') {
                        $checkboxInputEl->attr($key, $value);
                    }
                }
                if ($checked) {
                    $checkboxInputEl->attr('checked', '');
                }
                $checkboxLabelEl->child($checkboxInputEl);
                $checkboxLabelEl->child(Element::make('span')->text($label));
                $groupEl->child($checkboxLabelEl);
                break;

            case 'radio':
                foreach ($field['choices'] as $value => $labelText) {
                    $radioLabelEl = Element::make('label')->class('ux-form-radio');
                    $radioInputEl = Element::make('input')
                        ->attr('type', 'radio')
                        ->attr('name', $name)
                        ->attr('value', (string)$value);
                    foreach ($inputAttrs as $key => $attrValue) {
                        if ($key !== 'name') {
                            $radioInputEl->attr($key, $attrValue);
                        }
                    }
                    if (($field['value'] ?? '') === (string)$value) {
                        $radioInputEl->attr('checked', '');
                    }
                    $radioLabelEl->child($radioInputEl);
                    $radioLabelEl->child(Element::make('span')->text($labelText));
                    $groupEl->child($radioLabelEl);
                }
                break;

            case 'file':
                $fileInputEl = Element::make('input')
                    ->attr('type', 'file');
                foreach ($inputAttrs as $key => $value) {
                    $fileInputEl->attr($key, $value);
                }
                $groupEl->child($fileInputEl);
                break;

            default:
                $inputEl = Element::make('input')
                    ->attr('type', $type);
                foreach ($inputAttrs as $key => $value) {
                    $inputEl->attr($key, $value);
                }
                if (isset($field['value'])) {
                    $inputEl->attr('value', (string)$field['value']);
                }
                $groupEl->child($inputEl);
        }

        if (isset($field['help'])) {
            $groupEl->child(Element::make('span')->class('ux-form-help')->text($field['help']));
        }

        return $groupEl;
    }

    private function buildFieldAttrs(array $field): array
    {
        $attrs = [];
        $attrs['id'] = $field['name'];
        $attrs['name'] = $field['name'];

        if (isset($field['placeholder'])) {
            $attrs['placeholder'] = $field['placeholder'];
        }

        if ($field['required'] ?? false) {
            $attrs['required'] = '';
        }

        if (isset($field['disabled']) && $field['disabled']) {
            $attrs['disabled'] = '';
        }

        if (isset($field['readonly']) && $field['readonly']) {
            $attrs['readonly'] = '';
        }

        if (isset($this->liveBind[$field['name']])) {
            $attrs['data-bind'] = $this->liveBind[$field['name']];
        }

        if (isset($field['class'])) {
            $attrs['class'] = $field['class'];
        } else {
            $attrs['class'] = 'ux-form-input';
        }

        if (isset($field['autocomplete'])) {
            $attrs['autocomplete'] = $field['autocomplete'];
        } else {
            $type = $field['type'] ?? 'text';
            $autocompleteMap = [
                'password' => 'current-password',
                'email' => 'email',
                'username' => 'username',
                'tel' => 'tel',
            ];
            if (isset($autocompleteMap[$type])) {
                $attrs['autocomplete'] = $autocompleteMap[$type];
            }
        }

        return $attrs;
    }
}
