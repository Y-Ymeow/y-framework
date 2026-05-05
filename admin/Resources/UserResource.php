<?php

namespace Admin\Resources;

use Admin\Contracts\Resource\AdminResource;
use Admin\Contracts\Resource\BaseResource;
use Admin\Auth\User;
use Admin\Auth\Role;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\View\Base\Element;
use Framework\UX\UI\Button;

#[AdminResource(
    name: 'users',
    model: User::class,
    title: '用户管理',
    icon: 'people',
    group: 'admin.system',
    sort: 51,
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

    public static function getTitle(): string|array
    {
        return ['admin:users.title', [], '用户管理'];
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/users';
    }

    public function configureForm(FormBuilder $form): void
    {
        $form->text('name', t('admin:users.name'), ['required' => true])
            ->email('email', t('admin:users.email'), ['required' => true]);

        $form->section(t('admin:users.role'));
        $roles = Role::all();
        foreach ($roles as $role) {
            $form->checkbox("role_{$role['id']}", $role['name'], [
                'value' => (string)$role['id'],
            ]);
        }
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('name', t('admin:users.name'))
            ->column('email', t('admin:users.email'))
            ->column('created_at', t('admin:users.created_at'))
            ->rowActions(function ($row, $rowKey, $index) {
                return [
                    Button::make()
                        ->label(t('admin.edit'))
                        ->secondary()
                        ->sm()
                        ->liveAction('editRow')
                        ->data('action-params', json_encode(['rowKey' => $rowKey])),
                ];
            });
    }

    public function getListBeforeTable(): mixed
    {
        return Element::make('div')->class('admin-list-stats')->intl('admin:users.title');
    }

    public function getFormHeader(bool $isEdit, ?object $record = null): mixed
    {
        if ($isEdit && $record) {
            return Element::make('div')->class('admin-form-info')
                ->child(Element::make('p')->intl('admin:users.last_modified') . ': ' . ($record->updated_at ?? t('admin.unknown')));
        }
        return null;
    }

    public function getLiveActions(): array
    {
        return [];
    }
}
