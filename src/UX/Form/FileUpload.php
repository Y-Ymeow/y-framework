<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

class FileUpload extends FormField
{
    protected bool $multiple = false;
    protected string $accept = '';
    protected int $maxSize = 10240;

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function accept(string $accept): static
    {
        $this->accept = $accept;
        return $this;
    }

    public function images(): static
    {
        return $this->accept('image/*');
    }

    public function documents(): static
    {
        return $this->accept('.pdf,.doc,.docx,.xls,.xlsx');
    }

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
                    Element::make('span')->class('ux-file-button')->text(t('ux.select_file')),
                    Element::make('span')->class('ux-file-name')->text(t('ux.no_file_selected'))
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
