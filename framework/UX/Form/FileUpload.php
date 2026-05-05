<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

/**
 * 文件上传
 *
 * 用于文件上传，支持多选、文件类型限制、最大文件大小、图片/文档预设、预览。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example FileUpload::make()->name('avatar')->label('头像')->images()
 * @ux-example FileUpload::make()->name('files')->label('附件')->multiple()->documents()->maxSize(5120)
 * @ux-js-component file-upload.js
 * @ux-css form.css
 */
class FileUpload extends FormField
{
    protected bool $multiple = false;
    protected string $accept = '';
    protected int $maxSize = 10240;

    /**
     * 启用多选模式
     * @param bool $multiple 是否多选
     * @return static
     * @ux-example FileUpload::make()->multiple()
     * @ux-default true
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * 设置接受的文件类型
     * @param string $accept 接受类型（如 image/* 或 .pdf,.doc）
     * @return static
     * @ux-example FileUpload::make()->accept('image/*')
     */
    public function accept(string $accept): static
    {
        $this->accept = $accept;
        return $this;
    }

    /**
     * 仅接受图片文件
     * @return static
     * @ux-example FileUpload::make()->images()
     */
    public function images(): static
    {
        return $this->accept('image/*');
    }

    /**
     * 仅接受文档文件
     * @return static
     * @ux-example FileUpload::make()->documents()
     */
    public function documents(): static
    {
        return $this->accept('.pdf,.doc,.docx,.xls,.xlsx');
    }

    /**
     * 设置最大文件大小（KB）
     * @param int $kb 最大大小（KB）
     * @return static
     * @ux-example FileUpload::make()->maxSize(5120)
     * @ux-default 10240
     */
    public function maxSize(int $kb): static
    {
        $this->maxSize = $kb;
        return $this;
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $uploadEl = Element::make('div')->class('ux-file-upload');

        $fileInputEl = Element::make('input')
            ->attr('type', 'file')
            ->class('ux-file-input')
            ->data('max-size', (string)$this->maxSize);

        if ($this->multiple) {
            $fileInputEl->attr('multiple', '');
        }

        if ($this->accept) {
            $fileInputEl->attr('accept', $this->accept);
        }

        if ($this->required) {
            $fileInputEl->attr('required', '');
        }

        $uploadEl->child($fileInputEl);

        $uploadEl->child(
            Element::make('label')
                ->class('ux-file-label')
                ->children(
                    Element::make('span')->class('ux-file-button')->intl('ux:file-upload.select_file'),
                    Element::make('span')->class('ux-file-name')->intl('ux:file-upload.no_file_selected')
                )
        );

        $uploadEl->child(Element::make('div')->class('ux-file-preview'));

        $groupEl->child($uploadEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
