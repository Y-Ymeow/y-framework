<?php

declare(strict_types=1);

namespace Admin\Pages;

use Admin\Contracts\Live\AdminLayout;
use Admin\Contracts\Page\PageInterface;
use Admin\Content\Media;
use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\State;
use Framework\Http\Upload\Upload;
use Framework\Http\Response\Response;
use Framework\View\Base\Element;

class MediaPage extends LiveComponent implements PageInterface
{
    #[State]
    public string $viewMode = 'grid';

    #[State]
    public string $search = '';

    #[State]
    public string $filterType = 'all';

    #[State]
    public int $editingMediaId = 0;

    #[State]
    public string $editTitle = '';

    #[State]
    public string $editAlt = '';

    #[State]
    public int $page = 1;

    #[State]
    public int $perPage = 24;

    public static function getName(): string
    {
        return 'media';
    }

    public static function getTitle(): string|array
    {
        return ['admin:media.title', [], '媒体库'];
    }

    public static function getIcon(): string
    {
        return 'folder2-open';
    }

    public static function getGroup(): string
    {
        return '';
    }

    public static function getSort(): int
    {
        return 20;
    }

    public static function getRoutes(): array
    {
        return [
            'admin.media' => [
                'method' => 'GET',
                'path' => '/media',
                'handler' => function () {
                    return static::renderPage();
                },
            ],
            'admin.media.upload' => [
                'method' => 'POST',
                'path' => '/media/upload',
                'handler' => [static::class, 'handleUpload'],
            ],
        ];
    }

    public static function renderPage()
    {
        $page = new static();
        $page->named('admin-page-media');

        $layout = new AdminLayout();
        $layout->activeMenu = 'media';
        $layout->setContent($page);

        return $layout;
    }

    public static function handleUpload()
    {
        $files = Upload::multiple('files');
        if (empty($files)) {
            $single = Upload::from('file');
            if ($single) $files = [$single];
        }

        if (empty($files)) {
            return Response::json(['success' => false, 'message' => 'No file uploaded'], 400);
        }

        $results = [];

        foreach ($files as $file) {
            if (!$file->isValid()) {
                $results[] = ['success' => false, 'message' => $file->getErrorMessage()];
                continue;
            }

            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'video/mp4', 'application/pdf'];
            $errors = $file->allowedMimes($allowedMimes)->maxSize(10 * 1024 * 1024)->validate();
            if (!empty($errors)) {
                $results[] = ['success' => false, 'message' => implode(', ', $errors)];
                continue;
            }

            $datePath = date('Y/m');
            $directory = paths()->uploads() . '/' . $datePath;
            $storedName = $file->store($directory);

            $media = Media::create([
                'disk' => 'uploads',
                'path' => $datePath . '/' . $storedName,
                'filename' => $file->getName(),
                'extension' => $file->getExtension(),
                'mime_type' => $file->getMime(),
                'size' => $file->getSize(),
                'alt' => '',
                'title' => pathinfo($file->getName(), PATHINFO_FILENAME),
            ]);

            $data = $media->toArray();
            $results[] = [
                'success' => true,
                'id' => $data['id'] ?? $media->id,
                'url' => $media->getUrl(),
                'thumbnail' => $media->getThumbnailUrl(),
                'filename' => $data['filename'] ?? $file->getName(),
            ];
        }

        return Response::json(['success' => true, 'results' => $results]);
    }

    #[LiveAction]
    public function deleteMedia(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) return;

        $media = Media::find($id);
        if (!$media) return;

        $fullPath = paths()->uploads() . '/' . ($media->path ?? '');
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }

        Media::destroy($id);
        $this->toast('已删除');
        $this->refresh('media-grid');
    }

    #[LiveAction]
    public function editMedia(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) return;

        $media = Media::find($id);
        if (!$media) return;

        $this->editingMediaId = $id;
        $data = $media->toArray();
        $this->editTitle = $data['title'] ?? '';
        $this->editAlt = $data['alt'] ?? '';

        $this->refresh('media-grid');
    }

    #[LiveAction]
    public function saveMedia(array $params): void
    {
        $id = (int)($params['id'] ?? $this->editingMediaId);
        if ($id === 0) return;

        $media = Media::find($id);
        if (!$media) return;

        $media->title = trim($params['title'] ?? $this->editTitle);
        $media->alt = trim($params['alt'] ?? $this->editAlt);
        $media->save();

        $this->editingMediaId = 0;
        $this->toast('已更新');
        $this->refresh('media-grid');
    }

    #[LiveAction]
    public function cancelEdit(): void
    {
        $this->editingMediaId = 0;
        $this->refresh('media-grid');
    }

    #[LiveAction]
    public function setViewMode(array $params): void
    {
        $this->viewMode = ($params['mode'] ?? '') === 'list' ? 'list' : 'grid';
        $this->refresh('media-grid');
    }

    #[LiveAction]
    public function setFilterType(array $params): void
    {
        $this->filterType = $params['type'] ?? 'all';
        $this->page = 1;
        $this->refresh('media-grid');
    }

    #[LiveAction]
    public function setPage(array $params): void
    {
        $this->page = max(1, (int)($params['page'] ?? 1));
        $this->refresh('media-grid');
    }

    public function render(): Element
    {
        $wrapper = Element::make('div')->class('media-library');

        $header = Element::make('div')->class('media-library-header');
        $header->child(Element::make('h1')->class('media-library-title')->intl('admin:media.title', [], '媒体库'));
        $wrapper->child($header);

        $wrapper->child($this->renderUploadArea());
        $wrapper->child($this->renderToolbar());
        $wrapper->child($this->renderGrid());

        return $wrapper;
    }

    protected function renderUploadArea(): Element
    {
        $area = Element::make('div')
            ->class('media-upload-area')
            ->attr('data-media-upload', '')
            ->attr('data-upload-url', '/admin/media/upload');

        $content = Element::make('div')->class('media-upload-content');
        $content->child(Element::make('i')->class('bi', 'bi-cloud-arrow-up', 'media-upload-icon'));
        $content->child(Element::make('div')->class('media-upload-text')->text('点击或拖拽文件到此区域上传'));
        $content->child(Element::make('div')->class('media-upload-hint')->text('支持 JPG、PNG、GIF、WebP、SVG、MP4、PDF，单文件最大 10MB'));

        $input = Element::make('input')
            ->attr('type', 'file')
            ->attr('name', 'files')
            ->attr('multiple', 'multiple')
            ->attr('accept', 'image/*,video/mp4,application/pdf')
            ->class('media-upload-input')
            ->style('display:none');

        $area->child($input);
        $area->child($content);

        return $area;
    }

    protected function renderToolbar(): Element
    {
        $toolbar = Element::make('div')->class('media-toolbar');

        $filters = Element::make('div')->class('media-toolbar-filters');
        $types = [
            'all' => '全部',
            'image' => '图片',
            'video' => '视频',
            'document' => '文档',
        ];
        foreach ($types as $type => $label) {
            $btn = Element::make('button')
                ->class('media-filter-btn', $this->filterType === $type ? 'active' : '')
                ->attr('data-action:click', 'setFilterType()')
                ->attr('data-action-params', json_encode(['type' => $type], JSON_UNESCAPED_UNICODE))
                ->text($label);
            $filters->child($btn);
        }
        $toolbar->child($filters);

        $viewToggle = Element::make('div')->class('media-toolbar-view');
        $viewToggle->child(
            Element::make('button')
                ->class('media-view-btn', $this->viewMode === 'grid' ? 'active' : '')
                ->attr('data-action:click', 'setViewMode()')
                ->attr('data-action-params', json_encode(['mode' => 'grid'], JSON_UNESCAPED_UNICODE))
                ->html('<i class="bi bi-grid-3x3-gap"></i>')
        );
        $viewToggle->child(
            Element::make('button')
                ->class('media-view-btn', $this->viewMode === 'list' ? 'active' : '')
                ->attr('data-action:click', 'setViewMode()')
                ->attr('data-action-params', json_encode(['mode' => 'list'], JSON_UNESCAPED_UNICODE))
                ->html('<i class="bi bi-list-ul"></i>')
        );
        $toolbar->child($viewToggle);

        return $toolbar;
    }

    protected function renderGrid(): Element
    {
        $container = Element::make('div')
            ->class('media-grid-container')
            ->liveFragment('media-grid');

        $query = Media::query();
        if ($this->filterType === 'image') {
            $query->where('mime_type', 'like', 'image/%');
        } elseif ($this->filterType === 'video') {
            $query->where('mime_type', 'like', 'video/%');
        } elseif ($this->filterType === 'document') {
            $query->where('extension', 'in', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
        }

        if (!empty($this->search)) {
            $query->where('filename', 'like', '%' . $this->search . '%');
        }

        $total = $query->count();
        $totalPages = max(1, (int)ceil($total / $this->perPage));
        $this->page = min($this->page, $totalPages);

        $items = $query->orderBy('created_at', 'desc')
            ->offset(($this->page - 1) * $this->perPage)
            ->limit($this->perPage)
            ->get();

        if (empty($items)) {
            $container->child(
                Element::make('div')->class('media-grid-empty')->text('暂无媒体文件')
            );
            return $container;
        }

        $grid = Element::make('div')->class('media-grid', 'media-grid-' . $this->viewMode);

        foreach ($items as $itemData) {
            $m = is_array($itemData) ? $itemData : $itemData->toArray();
            $id = (int)($m['id'] ?? 0);
            $filename = $m['filename'] ?? '';
            $mimeType = $m['mime_type'] ?? '';
            $size = (int)($m['size'] ?? 0);
            $isImage = str_starts_with($mimeType, 'image/');
            $isVideo = str_starts_with($mimeType, 'video/');
            $path = $m['path'] ?? '';
            $url = $m['disk'] === 'public' ? '/storage/' . $path : '/media/' . $path;
            $isEditing = $id === $this->editingMediaId;

            $card = Element::make('div')->class('media-card', $isEditing ? 'editing' : '');

            if ($this->viewMode === 'grid') {
                $thumb = Element::make('div')->class('media-card-thumb');
                if ($isImage) {
                    $thumb->child(
                        Element::make('img')->attr('src', $url . '?w=300&h=300&fit=true')->attr('alt', $m['alt'] ?? $filename)->attr('loading', 'lazy')
                    );
                } elseif ($isVideo) {
                    $thumb->child(Element::make('div')->class('media-card-icon')->html('<i class="bi bi-play-circle"></i>'));
                } else {
                    $ext = strtoupper($m['extension'] ?? 'FILE');
                    $thumb->child(Element::make('div')->class('media-card-ext')->text($ext));
                }
                $card->child($thumb);
            }

            $info = Element::make('div')->class('media-card-info');

            if ($isEditing) {
                $editForm = Element::make('div')->class('media-card-edit');
                $editForm->child(
                    Element::make('div')->class('media-form-group')->children(
                        Element::make('label')->class('media-form-label')->text('标题'),
                        Element::make('input')
                            ->class('media-input', 'media-input-sm')
                            ->attr('type', 'text')
                            ->attr('data-live-model', 'editTitle')
                            ->attr('value', $this->editTitle)
                    )
                );
                $editForm->child(
                    Element::make('div')->class('media-form-group')->children(
                        Element::make('label')->class('media-form-label')->text('替代文本'),
                        Element::make('input')
                            ->class('media-input', 'media-input-sm')
                            ->attr('type', 'text')
                            ->attr('data-live-model', 'editAlt')
                            ->attr('value', $this->editAlt)
                    )
                );
                $editForm->child(
                    Element::make('div')->class('media-card-edit-actions')->children(
                        Element::make('button')
                            ->class('media-btn', 'media-btn-primary', 'media-btn-sm')
                            ->attr('data-action:click', 'saveMedia()')
                            ->attr('data-action-params', json_encode(['id' => $id], JSON_UNESCAPED_UNICODE))
                            ->text('保存'),
                        Element::make('button')
                            ->class('media-btn', 'media-btn-ghost', 'media-btn-sm')
                            ->attr('data-action:click', 'cancelEdit()')
                            ->text('取消')
                    )
                );
                $info->child($editForm);
            } else {
                $info->child(Element::make('div')->class('media-card-name')->text($filename));
                $info->child(Element::make('div')->class('media-card-meta')->text($this->formatSize($size)));

                $actions = Element::make('div')->class('media-card-actions');
                $actions->child(
                    Element::make('button')
                        ->class('media-card-action')
                        ->attr('data-action:click', 'editMedia()')
                        ->attr('data-action-params', json_encode(['id' => $id], JSON_UNESCAPED_UNICODE))
                        ->html('<i class="bi bi-pencil"></i>')
                );
                $actions->child(
                    Element::make('button')
                        ->class('media-card-action', 'media-card-action-danger')
                        ->attr('data-action:click', 'deleteMedia()')
                        ->attr('data-action-params', json_encode(['id' => $id], JSON_UNESCAPED_UNICODE))
                        ->html('<i class="bi bi-trash3"></i>')
                );
                $info->child($actions);
            }

            $card->child($info);
            $grid->child($card);
        }

        $container->child($grid);

        if ($totalPages > 1) {
            $container->child($this->renderPagination($totalPages));
        }

        return $container;
    }

    protected function renderPagination(int $totalPages): Element
    {
        $pagination = Element::make('div')->class('media-pagination');

        if ($this->page > 1) {
            $pagination->child(
                Element::make('button')
                    ->class('media-page-btn')
                    ->attr('data-action:click', 'setPage()')
                    ->attr('data-action-params', json_encode(['page' => $this->page - 1], JSON_UNESCAPED_UNICODE))
                    ->html('<i class="bi bi-chevron-left"></i>')
            );
        }

        $start = max(1, $this->page - 2);
        $end = min($totalPages, $this->page + 2);

        for ($i = $start; $i <= $end; $i++) {
            $pagination->child(
                Element::make('button')
                    ->class('media-page-btn', $i === $this->page ? 'active' : '')
                    ->attr('data-action:click', 'setPage()')
                    ->attr('data-action-params', json_encode(['page' => $i], JSON_UNESCAPED_UNICODE))
                    ->text((string)$i)
            );
        }

        if ($this->page < $totalPages) {
            $pagination->child(
                Element::make('button')
                    ->class('media-page-btn')
                    ->attr('data-action:click', 'setPage()')
                    ->attr('data-action-params', json_encode(['page' => $this->page + 1], JSON_UNESCAPED_UNICODE))
                    ->html('<i class="bi bi-chevron-right"></i>')
            );
        }

        return $pagination;
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
