<?php

namespace App\Admin\Resources;

use Framework\Admin\Attribute\AdminResource;
use Framework\Routing\Attribute\Get;
use Framework\Routing\Attribute\Post;
use Framework\Routing\Attribute\Put;
use Framework\Routing\Attribute\Delete;
use Framework\Admin\Resource\ResourceInterface;
use Framework\Http\Response;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\Database\Model;

#[AdminResource(
    name: 'users',
    model: User::class,
    title: '用户管理',
    icon: 'heroicon.users',
)]
class UserResource implements ResourceInterface
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
            // 直接注册行操作组件
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

    /**
     * 手动注册 LiveActions
     */
    public function getLiveActions(): array
    {
        return [];
    }

    #[Get('/')]
    public function index(): Response
    {
        $table = new DataTable();
        $this->configureTable($table);
        
        $users = User::query()->orderByDesc('id')->limit(20)->get();
        $table->dataSource($users->toArray());
        
        return view('admin.users.index', [
            'table' => $table,
        ]);
    }

    #[Get('/create')]
    public function create(): Response
    {
        $form = new FormBuilder();
        $form->action('/admin/users')->method('POST');
        $this->configureForm($form);
        
        return view('admin.users.form', [
            'form' => $form,
            'title' => '创建用户',
        ]);
    }

    #[Post('/')]
    public function store(): Response
    {
        $user = User::create(request()->validate([
            'name' => 'required',
            'email' => 'required|email',
        ]));
        
        return redirect('/admin/users')->with('success', '用户创建成功');
    }

    #[Get('/{id}/edit')]
    public function edit(int $id): Response
    {
        $user = User::findOrFail($id);
        
        $form = new FormBuilder();
        $form->action("/admin/users/{$id}")->method('PUT');
        $this->configureForm($form);
        
        return view('admin.users.form', [
            'form' => $form,
            'model' => $user,
            'title' => '编辑用户',
        ]);
    }

    #[Put('/{id}')]
    public function update(int $id): Response
    {
        $user = User::findOrFail($id);
        $user->update(request()->validate([
            'name' => 'required',
            'email' => 'required|email',
        ]));
        
        return redirect('/admin/users')->with('success', '用户更新成功');
    }

    #[Delete('/{id}')]
    public function destroy(int $id): Response
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        return redirect('/admin/users')->with('success', '用户删除成功');
    }
}

class User extends Model
{
    protected string $table = 'users';
}