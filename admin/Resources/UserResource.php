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
        $form->text('name', ['admin:users.name', [], '姓名'], ['required' => true])
            ->email('email', ['admin:users.email', [], '邮箱'], ['required' => true]);

        $form->section(['admin:users.role', [], '角色']);
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
            ->column('name', ['admin:users.name', [], '姓名'])
            ->column('email', ['admin:users.email', [], '邮箱'])
            ->column('created_at', ['admin:users.created_at', [], '创建时间'])
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
                ->child(
                    Element::make('p')->child(
                        Element::make('span')->intl('admin:users.last_modified', [], '最后修改')
                    )->child(
                        Element::make('span')->text(': ' . ($record->updated_at ?? t('admin.unknown')))
                    )
                );
        }
        return null;
    }

    public function getLiveActions(): array
    {
        return [];
    }
}
