<?php

namespace Admin\Resources;

use Admin\Contracts\Resource\AdminResource;
use Admin\Contracts\Resource\BaseResource;
use Admin\Content\Media;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\View\Base\Element;
use Framework\UX\UI\Button;

#[AdminResource(
    name: 'media',
    model: Media::class,
    title: '媒体库',
    icon: 'folder2-open',
    sort: 20,
)]
class MediaResource extends BaseResource
{
    public static function getName(): string
    {
        return 'media';
    }

    public static function getModel(): string
    {
        return Media::class;
    }

    public static function getTitle(): string|array
    {
        return ['admin:media.title', [], '媒体库'];
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/media';
    }

    public function configureForm(FormBuilder $form): void
    {
        $form->text('title', ['admin:media.title_field', [], '标题'], [])
            ->text('alt', ['admin:media.alt', [], '替代文本'], [])
            ->file('file', ['admin:media.upload', [], '上传'], []);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('filename', ['admin:media.filename', [], '文件名'])
            ->column('mime_type', ['admin:media.mime_type', [], '类型'])
            ->column('size', ['admin:media.size', [], '大小'])
            ->column('created_at', ['admin:media.created_at', [], '创建时间'])
            ->rowActions(function ($row, $rowKey, $index) {
                return [
                    Button::make()
                        ->label(t('admin.edit'))
                        ->secondary()
                        ->sm()
                        ->liveAction('editRow')
                        ->data('action-params', json_encode(['rowKey' => $rowKey])),
                    Button::make()
                        ->label(t('admin.delete'))
                        ->danger()
                        ->sm()
                        ->liveAction('deleteRow')
                        ->data('action-params', json_encode(['rowKey' => $rowKey])),
                ];
            });
    }

    public function getListBeforeTable(): mixed
    {
        return Element::make('div')->class('admin-list-stats')->intl('admin:media.title');
    }

    public function getLiveActions(): array
    {
        return [];
    }
}
