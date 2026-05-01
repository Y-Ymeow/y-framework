<?php

namespace App\Admin\Resources;

use Framework\Admin\Attribute\AdminResource;
use Framework\Admin\Resource\BaseResource;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\View\Base\Element;
use Framework\UX\UI\Button;
use Framework\Auth\User;

#[AdminResource(
    name: 'users',
    model: User::class,
    title: '用户管理',
    icon: 'heroicon.users',
)]
class UserResource extends BaseResource
{
    public static function getName(): string
    {
        return 'users';
    }

    public static function getModel(): string
    {
        return User::class;
    }

    public static function getTitle(): string
    {
        return t('admin.user_management');
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/users';
    }

    public function configureForm(FormBuilder $form): void
    {
        $form->text('name', t('admin.fields.name'), ['required' => true])
            ->email('email', t('email'), ['required' => true])
            ->select('role', t('admin.fields.role'), [], [
                'admin' => t('admin.roles.admin'),
                'editor' => t('admin.roles.editor'),
                'viewer' => t('admin.roles.viewer'),
            ]);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('name', t('admin.fields.name'))
            ->column('email', t('email'))
            ->column('role', t('admin.fields.role'))
            ->column('created_at', t('admin.fields.created_at'))
            ->rowActions(function ($row, $rowKey, $index) {
                return [
                    Button::make()
                        ->label(t('edit'))
                        ->secondary()
                        ->sm()
                        ->liveAction('editRow')
                        ->data('action-params', json_encode(['rowKey' => $rowKey])),
                ];
            });
    }

    public function getListBeforeTable(): mixed
    {
        return Element::make('div')->class('admin-list-stats')->text(t('admin.user_management'));
    }

    public function getFormHeader(bool $isEdit, ?object $record = null): mixed
    {
        if ($isEdit && $record) {
            return Element::make('div')->class('admin-form-info')
                ->child(Element::make('p')->text(t('admin.last_modified') . ': ' . ($record->updated_at ?? t('admin.unknown'))));
        }
        return null;
    }

    public function getLiveActions(): array
    {
        return [];
    }
}
