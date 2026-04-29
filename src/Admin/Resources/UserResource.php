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
        return '用户管理';
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/users';
    }

    public function configureForm(FormBuilder $form): void
    {
        $form->text('name', '姓名', ['required' => true])
            ->email('email', '邮箱', ['required' => true])
            ->select('role', '角色', [], ['admin' => '管理员', 'editor' => '编辑', 'viewer' => '查看']);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('name', '姓名')
            ->column('email', '邮箱')
            ->column('role', '角色')
            ->column('created_at', '创建时间')
            ->rowActions(function ($row, $rowKey, $index) {
                return [
                    Button::make()
                        ->label('编辑')
                        ->secondary()
                        ->sm()
                        ->liveAction('editRow')
                        ->data('action-params', json_encode(['rowKey' => $rowKey])),
                ];
            });
    }

    public function getListBeforeTable(): mixed
    {
        return Element::make('div')->class('admin-list-stats')->text('用户管理');
    }

    public function getFormHeader(bool $isEdit, ?object $record = null): mixed
    {
        if ($isEdit && $record) {
            return Element::make('div')->class('admin-form-info')
                ->child(Element::make('p')->text('最后修改时间: ' . ($record->updated_at ?? '未知')));
        }
        return null;
    }

    public function getLiveActions(): array
    {
        return [];
    }
}
