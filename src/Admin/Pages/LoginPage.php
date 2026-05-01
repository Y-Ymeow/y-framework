<?php

declare(strict_types=1);

namespace Framework\Admin\Pages;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\View\Base\Element;

class LoginPage extends LiveComponent
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public static function getName(): string
    {
        return 'login';
    }

    public static function getTitle(): string
    {
        return t('login');
    }

    #[LiveAction]
    public function login(): void
    {
        if (empty($this->email) || empty($this->password)) {
            $this->toast(t('admin.login_required'), 'error');
            return;
        }

        // 这里可以接入实际的认证逻辑
        // $success = auth()->attempt(['email' => $this->email, 'password' => $this->password]);

        $this->toast(t('admin.login_success'));
        $this->redirect('/admin');
    }

    public function render(): string|Element
    {
        $wrapper = Element::make('div')
            ->class('admin-login', 'min-h-screen', 'flex', 'items-center', 'justify-center', 'bg-gray-50');

        $card = Element::make('div')
            ->class('bg-white', 'rounded-xl', 'shadow-sm', 'border', 'border-gray-200', 'w-full', 'max-w-md', 'p-8');

        $header = Element::make('div')->class('text-center', 'mb-8');
        $header->child(Element::make('h1')->class('text-2xl', 'font-bold', 'text-gray-900')->text(t('admin.admin_panel')));
        $header->child(Element::make('p')->class('mt-2', 'text-gray-500')->text(t('admin.login_prompt')));
        $card->child($header);

        $form = Element::make('form')
            ->class('space-y-6')
            ->attr('method', 'POST')
            ->liveAction('login', 'submit');

        // Email
        $emailGroup = Element::make('div');
        $emailGroup->child(Element::make('label')
            ->class('block', 'text-sm', 'font-medium', 'text-gray-700', 'mb-2')
            ->text(t('email')));
        $emailInput = Element::make('input')
            ->class('ux-form-input', 'w-full')
            ->attr('type', 'email')
            ->attr('name', 'email')
            ->attr('placeholder', 'your@email.com')
            ->attr('required', '')
            ->liveModel('email');
        $emailGroup->child($emailInput);
        $form->child($emailGroup);

        // Password
        $passwordGroup = Element::make('div');
        $passwordGroup->child(Element::make('label')
            ->class('block', 'text-sm', 'font-medium', 'text-gray-700', 'mb-2')
            ->text(t('password')));
        $passwordInput = Element::make('input')
            ->class('ux-form-input', 'w-full')
            ->attr('type', 'password')
            ->attr('name', 'password')
            ->attr('placeholder', '••••••••')
            ->attr('required', '')
            ->liveModel('password');
        $passwordGroup->child($passwordInput);
        $form->child($passwordGroup);

        // Remember
        $rememberGroup = Element::make('div')->class('flex', 'items-center');
        $rememberCheckbox = Element::make('input')
            ->class('ux-form-checkbox')
            ->attr('type', 'checkbox')
            ->attr('id', 'remember')
            ->liveModel('remember');
        $rememberLabel = Element::make('label')
            ->class('ml-2', 'text-sm', 'text-gray-600')
            ->attr('for', 'remember')
            ->text(t('remember_me'));
        $rememberGroup->child($rememberCheckbox);
        $rememberGroup->child($rememberLabel);
        $form->child($rememberGroup);

        // Submit
        $submitBtn = Element::make('button')
            ->class('ux-btn', 'ux-btn-primary', 'w-full')
            ->attr('type', 'submit')
            ->text(t('login'));
        $form->child($submitBtn);

        $card->child($form);
        $wrapper->child($card);

        return $wrapper;
    }
}
