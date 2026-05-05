<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

/**
 * 图片上传组件
 *
 * 支持图片上传、预览、裁剪、缩略图生成和云存储集成。
 * 支持单图和多图上传，可配置裁剪比例和缩略图尺寸。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example ImageUpload::make()->name('avatar')->label('头像')
 * @ux-example ImageUpload::make()->name('images')->label('图片集')->maxFiles(5)->multiple()
 * @ux-example ImageUpload::make()->name('cover')->label('封面')->croppable()->aspectRatio(16, 9)
 * @ux-live-support upload, remove
 * @ux-js-component image-upload.js
 * @ux-css image-upload.css
 * @ux-value-type array
 */
class ImageUpload extends FormField
{
    protected bool $multiple = false;
    protected int $maxFiles = 1;
    protected array $acceptedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    protected int $maxSize = 2048;
    protected array $thumbnails = [];
    protected bool $croppable = false;
    protected float $aspectRatio = 0;
    protected ?int $minWidth = null;
    protected ?int $minHeight = null;
    protected ?int $maxWidth = null;
    protected ?int $maxHeight = null;
    protected bool $showPreview = true;
    protected string $storage = 'local';
    protected ?string $bucket = null;
    protected ?string $path = null;
    protected ?string $cdnUrl = null;
    protected string $uploadAction = '';
    protected string $viewMode = 'grid';
    protected bool $draggable = true;

    /**
     * 设置多选模式
     * @param bool $multiple 是否多选
     * @return static
     * @ux-example ImageUpload::make()->name('images')->multiple()
     * @ux-default false
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * 设置最大上传文件数
     * @param int $max 最大文件数
     * @return static
     * @ux-example ImageUpload::make()->maxFiles(5)
     * @ux-default 1
     */
    public function maxFiles(int $max): static
    {
        $this->maxFiles = $max;
        return $this;
    }

    /**
     * 设置允许的图片格式
     * @param array $formats 格式列表：['jpg', 'png', 'gif', 'webp']
     * @return static
     * @ux-example ImageUpload::make()->acceptedFormats(['jpg', 'png', 'webp'])
     */
    public function acceptedFormats(array $formats): static
    {
        $this->acceptedFormats = $formats;
        return $this;
    }

    /**
     * 设置最大文件大小（KB）
     * @param int $kb 最大大小（KB）
     * @return static
     * @ux-example ImageUpload::make()->maxSize(2048)
     * @ux-default 2048
     */
    public function maxSize(int $kb): static
    {
        $this->maxSize = $kb;
        return $this;
    }

    /**
     * 配置缩略图尺寸
     * @param array $thumbnails 缩略图配置 ['sm' => ['width' => 150, 'height' => 150, 'crop' => true]]
     * @return static
     * @ux-example ImageUpload::make()->thumbnails(['thumb' => ['width' => 300, 'height' => 300, 'crop' => true]])
     */
    public function thumbnails(array $thumbnails): static
    {
        $this->thumbnails = $thumbnails;
        return $this;
    }

    /**
     * 启用图片裁剪
     * @param bool $croppable 是否可裁剪
     * @return static
     * @ux-example ImageUpload::make()->croppable()
     * @ux-default false
     */
    public function croppable(bool $croppable = true): static
    {
        $this->croppable = $croppable;
        return $this;
    }

    /**
     * 设置裁剪宽高比
     * @param float $ratio 宽高比（0 表示自由比例）
     * @return static
     * @ux-example ImageUpload::make()->aspectRatio(1)
     * @ux-default 0
     */
    public function aspectRatio(float $ratio): static
    {
        $this->aspectRatio = $ratio;
        return $this;
    }

    /**
     * 设置最小裁剪宽度
     * @param int $width 最小宽度（px）
     * @return static
     * @ux-example ImageUpload::make()->minWidth(200)
     */
    public function minWidth(int $width): static
    {
        $this->minWidth = $width;
        return $this;
    }

    /**
     * 设置最小裁剪高度
     * @param int $height 最小高度（px）
     * @return static
     * @ux-example ImageUpload::make()->minHeight(200)
     */
    public function minHeight(int $height): static
    {
        $this->minHeight = $height;
        return $this;
    }

    /**
     * 设置最大裁剪宽度
     * @param int $width 最大宽度（px）
     * @return static
     * @ux-example ImageUpload::make()->maxWidth(1920)
     */
    public function maxWidth(int $width): static
    {
        $this->maxWidth = $width;
        return $this;
    }

    /**
     * 设置最大裁剪高度
     * @param int $height 最大高度（px）
     * @return static
     * @ux-example ImageUpload::make()->maxHeight(1080)
     */
    public function maxHeight(int $height): static
    {
        $this->maxHeight = $height;
        return $this;
    }

    /**
     * 设置是否显示预览
     * @param bool $show 是否显示预览
     * @return static
     * @ux-example ImageUpload::make()->showPreview(false)
     * @ux-default true
     */
    public function showPreview(bool $show = true): static
    {
        $this->showPreview = $show;
        return $this;
    }

    /**
     * 设置存储方式
     * @param string $storage 存储方式：local/oss/s3
     * @return static
     * @ux-example ImageUpload::make()->storage('oss')
     * @ux-default 'local'
     */
    public function storage(string $storage): static
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * 设置存储桶名称（云存储）
     * @param string $bucket 桶名称
     * @return static
     * @ux-example ImageUpload::make()->bucket('my-bucket')
     */
    public function bucket(string $bucket): static
    {
        $this->bucket = $bucket;
        return $this;
    }

    /**
     * 设置存储路径
     * @param string $path 存储路径
     * @return static
     * @ux-example ImageUpload::make()->path('uploads/images')
     */
    public function path(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 设置 CDN 地址
     * @param string $url CDN 地址
     * @return static
     * @ux-example ImageUpload::make()->cdnUrl('https://cdn.example.com')
     */
    public function cdnUrl(string $url): static
    {
        $this->cdnUrl = $url;
        return $this;
    }

    /**
     * 设置上传接口地址
     * @param string $action 上传 URL
     * @return static
     * @ux-example ImageUpload::make()->uploadAction('/api/upload/image')
     */
    public function uploadAction(string $action): static
    {
        $this->uploadAction = $action;
        return $this;
    }

    /**
     * 设置视图模式
     * @param string $mode 模式：grid/list
     * @return static
     * @ux-example ImageUpload::make()->viewMode('list')
     * @ux-default 'grid'
     */
    public function viewMode(string $mode): static
    {
        $this->viewMode = $mode;
        return $this;
    }

    /**
     * 启用拖拽排序
     * @param bool $draggable 是否可拖拽
     * @return static
     * @ux-example ImageUpload::make()->draggable(false)
     * @ux-default true
     */
    public function draggable(bool $draggable = true): static
    {
        $this->draggable = $draggable;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $container = Element::make('div')
            ->class('ux-image-upload')
            ->class("ux-image-upload-{$this->viewMode}")
            ->data('max-files', (string)$this->maxFiles)
            ->data('max-size', (string)$this->maxSize)
            ->data('storage', $this->storage)
            ->data('accepted', implode(',', $this->acceptedFormats));

        if ($this->croppable) {
            $container->data('croppable', 'true');
            if ($this->aspectRatio > 0) {
                $container->data('aspect-ratio', (string)$this->aspectRatio);
            }
            if ($this->minWidth) {
                $container->data('min-width', (string)$this->minWidth);
            }
            if ($this->minHeight) {
                $container->data('min-height', (string)$this->minHeight);
            }
        }

        if ($this->uploadAction) {
            $container->data('upload-url', $this->uploadAction);
        }

        if ($this->cdnUrl) {
            $container->data('cdn-url', $this->cdnUrl);
        }

        if ($this->path) {
            $container->data('upload-path', $this->path);
        }

        $uploadArea = Element::make('div')
            ->class('ux-image-upload-area')
            ->attr('tabindex', '0');

        $uploadArea->child(
            Element::make('input')
                ->attr('type', 'file')
                ->class('ux-image-input')
                ->attr('accept', implode(',', array_map(fn($f) => "image/{$f}", $this->acceptedFormats)))
                ->attr('multiple', $this->multiple ? '' : null)
                ->attr('name', $this->name . ($this->multiple ? '[]' : ''))
        );

        $hint = Element::make('div')->class('ux-image-upload-hint');
        $hint->child(
            Element::make('i')->class('bi bi-cloud-arrow-up ux-image-upload-icon')
        );
        $hint->child(
            Element::make('p')->class('ux-image-upload-text')->intl('ux:image-upload.click_or_drag_to_upload')
        );
        $hint->child(
            Element::make('p')->class('ux-image-upload-desc')
                ->text("支持 " . implode(', ', array_map(fn($f) => strtoupper($f), $this->acceptedFormats)) . "，最大 {$this->maxSize}KB")
        );
        $uploadArea->child($hint);

        $container->child($uploadArea);

        if ($this->showPreview) {
            $preview = Element::make('div')
                ->class('ux-image-preview')
                ->class("ux-image-preview-{$this->viewMode}");

            if ($this->draggable && $this->multiple) {
                $preview->class('ux-image-preview-draggable');
            }

            if (!empty($this->thumbnails)) {
                $preview->data('thumbnails', json_encode($this->thumbnails, JSON_UNESCAPED_UNICODE));
            }

            $container->child($preview);
        }

        $container->child(
            Element::make('input')
                ->attr('type', 'hidden')
                ->attr('name', $this->name)
                ->class('ux-image-upload-value')
                ->data('ux-image-values', 'true')
        );

        $groupEl->child($container);

        $errorEl = $this->renderError();
        if ($errorEl) {
            $groupEl->child($errorEl);
        }

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
