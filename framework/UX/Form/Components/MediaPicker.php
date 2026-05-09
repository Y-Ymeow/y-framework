<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

use Admin\Content\Media;
use Framework\UX\Dialog\Modal;
use Framework\UX\UI\Button;
use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;

class MediaPicker extends BaseField
{
    protected string $type = 'media';
    protected string $accept = 'image/*';
    protected bool $multiple = false;
    protected ?int $maxSize = null;
    protected array $filterTypes = ['image', 'video', 'document'];

    public function getType(): string
    {
        return $this->type;
    }

    public function accept(string $accept): static
    {
        $this->accept = $accept;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function maxSize(int $kb): static
    {
        $this->maxSize = $kb;
        return $this;
    }

    public function render(): Element
    {
        AssetRegistry::getInstance()->inlineStyle('ux:media-picker', '
            .media-picker-modal { display: flex; flex-direction: column; gap: 1rem; }
            .media-picker-upload {
                border: 2px dashed #e5e7eb; border-radius: 0.5rem; padding: 1.5rem;
                text-align: center; background: #f9fafb; cursor: pointer; transition: all 0.2s;
            }
            .media-picker-upload:hover { border-color: #3b82f6; background: #f3f4f6; }
            .media-picker-upload.y-uploading { opacity: 0.6; cursor: wait; }
            .media-picker-upload-btn {
                background: none; border: none; color: #3b82f6; font-weight: 500;
                display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin: 0 auto;
            }
            .media-picker-filters { display: flex; gap: 0.5rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; }
            .media-picker-filter {
                background: none; border: none; padding: 0.25rem 0.75rem; border-radius: 0.375rem;
                font-size: 0.875rem; cursor: pointer; color: #6b7280;
            }
            .media-picker-filter.active { background: #3b82f6; color: #fff; }
            .media-picker-grid {
                display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 0.75rem; max-height: 400px; overflow-y: auto; padding: 0.5rem;
            }
            .media-picker-item {
                aspect-ratio: 1; border: 1px solid #e5e7eb; border-radius: 0.375rem;
                overflow: hidden; cursor: pointer; transition: transform 0.1s;
                display: flex; align-items: center; justify-content: center; background: #fff;
            }
            .media-picker-item:hover { transform: scale(1.05); border-color: #3b82f6; }
            .media-picker-item img { width: 100%; height: 100%; object-fit: cover; }
            .media-picker-item-icon { font-size: 1.5rem; color: #9ca3af; font-weight: bold; }
        ');

        $wrapper = $this->buildWrapper();
        $wrapper->child($this->buildLabel());

        $modalId = 'media-picker-' . $this->name;

        $value = $this->getValue() ?? '';
        $hasValue = !empty($value);

        $container = Element::make('div')->class('ux-form-media-picker');

        $preview = Element::make('div')->class('ux-form-media-preview', $hasValue ? 'has-image' : 'empty');
        if ($hasValue) {
            $preview->child(
                Element::make('img')
                    ->class('ux-form-media-preview-img')
                    ->attr('src', $value)
                    ->attr('alt', '')
            );
        } else {
            $preview->child(
                Element::make('div')->class('ux-form-media-placeholder')
                    ->html('<i class="bi bi-image"></i><span>未选择图片</span>')
            );
        }
        $container->child($preview);

        $hiddenInput = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', $this->name)
            ->attr('data-media-value', '')
            ->attr('value', $value);

        if ($this->submitMode) {
            $hiddenInput->attr('data-submit-field', $this->name);
        }

        $container->child($hiddenInput);

        $actions = Element::make('div')->class('ux-form-media-actions');
        $actions->child(
            Button::make()
                ->label('选择图片')
                ->variant('secondary')
                ->attr('data-ux-modal-open', $modalId)
        );
        if ($hasValue) {
            $removeBtn = Element::make('button')
                ->class('ux-form-media-remove')
                ->attr('type', 'button')
                ->attr('data-media-remove', '')
                ->html('<i class="bi bi-x"></i>');
            $actions->child($removeBtn);
        }
        $container->child($actions);

        $wrapper->child($container);
        $wrapper->child($this->buildModal($modalId));

        $help = $this->buildHelp();
        if ($help) {
            $wrapper->child($help);
        }

        return $wrapper;
    }

    protected function buildModal(string $modalId): Modal
    {
        $modal = Modal::make()
            ->title('选择图片')
            ->size('lg')
            ->content($this->renderModalBody($modalId))
            ->footer(
                Button::make()
                    ->label('取消')
                    ->variant('secondary')
                    ->attr('data-ux-modal-close', $modalId)
            );

        $modal->id($modalId);

        return $modal;
    }

    protected function renderModalBody(string $modalId): Element
    {
        $body = Element::make('div')->class('media-picker-modal');

        $body->child($this->renderUploadZone());
        $body->child($this->renderFilterTabs($modalId));
        $body->child($this->renderMediaGrid($modalId));

        return $body;
    }

    protected function renderUploadZone(): Element
    {
        $zone = Element::make('div')
            ->class('media-picker-upload')
            ->attr('data-live-upload', '');

        $fileInput = Element::make('input')
            ->attr('type', 'file')
            ->attr('accept', $this->accept)
            ->class('media-picker-upload-input')
            ->style('display:none');

        $hiddenInput = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('data-media-value', '')
            ->attr('value', '');

        $trigger = Element::make('div')
            ->class('media-picker-upload-btn')
            ->attr('data-media-trigger', '')
            ->html('<i class="bi bi-cloud-arrow-up"></i> <span>点击或拖拽上传新图片</span>');

        $zone->child($fileInput);
        $zone->child($hiddenInput);
        $zone->child($trigger);

        return $zone;
    }

    protected function renderFilterTabs(string $modalId): Element
    {
        $tabs = Element::make('div')->class('media-picker-filters');
        $tabs->child(
            Element::make('button')
                ->class('media-picker-filter active')
                ->attr('data-media-filter', 'all')
                ->text('全部')
        );
        $tabs->child(
            Element::make('button')
                ->class('media-picker-filter')
                ->attr('data-media-filter', 'image')
                ->text('图片')
        );
        $tabs->child(
            Element::make('button')
                ->class('media-picker-filter')
                ->attr('data-media-filter', 'video')
                ->text('视频')
        );
        $tabs->child(
            Element::make('button')
                ->class('media-picker-filter')
                ->attr('data-media-filter', 'document')
                ->text('文档')
        );

        return $tabs;
    }

    protected function renderMediaGrid(string $modalId): Element
    {
        $grid = Element::make('div')->class('media-picker-grid')
            ->liveFragment('media-grid-' . $this->name);

        $items = Media::query()
            ->orderBy('created_at', 'desc')
            ->limit(40)
            ->get();

        foreach ($items as $itemData) {
            $m = is_array($itemData) ? $itemData : $itemData->toArray();
            $mimeType = $m['mime_type'] ?? '';
            $isImage = str_starts_with($mimeType, 'image/');
            $url = '/media/' . ($m['path'] ?? '');

            $item = Element::make('div')
                ->class('media-picker-item')
                ->attr('data-media-url', $url)
                ->attr('data-media-mime', $mimeType)
                ->liveAction('selectMedia', 'click', [
                    'url' => $url,
                    'name' => $this->name,
                    'modalId' => $modalId
                ]);

            if ($isImage) {
                $item->child(
                    Element::make('img')
                        ->attr('src', $url . '?w=150&h=150&fit=true')
                        ->attr('alt', $m['alt'] ?? '')
                        ->attr('loading', 'lazy')
                );
            } else {
                $ext = strtoupper($m['extension'] ?? 'FILE');
                $item->child(
                    Element::make('div')->class('media-picker-item-icon')->text($ext)
                );
            }

            $grid->child($item);
        }

        return $grid;
    }
}

