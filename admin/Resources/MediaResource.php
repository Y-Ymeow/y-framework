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
        $form->text('title', t('admin:media.title_field'), [])
            ->text('alt', t('admin:media.alt'), [])
            ->file('file', t('admin:media.upload'), []);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('filename', t('admin:media.filename'))
            ->column('mime_type', t('admin:media.mime_type'))
            ->column('size', t('admin:media.size'))
            ->column('created_at', t('admin:media.created_at'))
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
