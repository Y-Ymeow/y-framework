<?php

namespace Admin\Resources;

use Admin\Contracts\Resource\AdminResource;
use Admin\Contracts\Resource\BaseResource;
use Admin\Auth\Role;
use Admin\Auth\Permission;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\View\Base\Element;
use Framework\UX\UI\Button;

#[AdminResource(
    name: 'roles',
    model: Role::class,
    title: '角色管理',
    icon: 'shield-lock',
    group: 'admin.system',
    sort: 52,
)]
class RoleResource extends BaseResource
{
    public static function getName(): string
    {
        return 'roles';
    }

    public static function getModel(): string
    {
        return Role::class;
    }

    public static function getTitle(): string|array
    {
        return ['admin:roles.title', [], '角色管理'];
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/roles';
    }

    public function configureForm(FormBuilder $form): void
    {
        $form->text('name', t('admin:roles.name'), ['required' => true])
            ->text('slug', t('admin:roles.slug'), ['required' => true])
            ->textarea('description', t('admin:roles.description'), []);

        $form->section(t('admin:roles.permissions'));
        $permissionsByModule = Permission::getByModule();
        foreach ($permissionsByModule as $module => $perms) {
            foreach ($perms as $perm) {
                $form->checkbox("permission_{$perm['id']}", $perm['name'], [
                    'value' => (string)$perm['id'],
                ]);
            }
        }
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('name', t('admin:roles.name'))
            ->column('slug', t('admin:roles.slug'))
            ->column('description', t('admin:roles.description'))
            ->column('is_system', t('admin:roles.is_system'))
            ->column('created_at', t('admin:roles.created_at'))
            ->rowActions(function ($row, $rowKey, $index) {
                $actions = [
                    Button::make()
                        ->label(t('admin.edit'))
                        ->secondary()
                        ->sm()
                        ->liveAction('editRow')
                        ->data('action-params', json_encode(['rowKey' => $rowKey])),
                ];

                if (empty($row['is_system']) || !$row['is_system']) {
                    $actions[] = Button::make()
                        ->label(t('admin.delete'))
                        ->danger()
                        ->sm()
                        ->liveAction('deleteRow')
                        ->data('action-params', json_encode(['rowKey' => $rowKey]));
                }

                return $actions;
            });
    }

    public function getListBeforeTable(): mixed
    {
        return Element::make('div')->class('admin-list-stats')->intl('admin:roles.title');
    }

    public function getFormHeader(bool $isEdit, ?object $record = null): mixed
    {
        if ($isEdit && $record && !empty($record['is_system'])) {
            return Element::make('div')->class('admin-form-info')
                ->child(Element::make('p')->intl('admin:roles.system_role_cannot_delete'));
        }
        return null;
    }

    public function getLiveActions(): array
    {
        return [];
    }
}
