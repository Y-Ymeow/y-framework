<?php

declare(strict_types=1);

namespace Admin\Pages;

use Admin\Auth\AuthManager;
use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\State;
use Framework\UX\UI\Button;
use Framework\UX\Form\Input;
use Framework\UX\Form\Checkbox;
use Framework\View\Base\Element;

class LoginPage extends LiveComponent
{
    #[State]
    public string $email = '';
    #[State]
    public string $password = '';
    #[State]
    public bool $remember = false;
    #[State]
    public array $errors = [];

    public static function getName(): string
    {
        return 'login';
    }

    public static function getTitle(): string
    {
        return t('login');
    }

    #[LiveAction]
    public function login(array $params = []): void
    {
        $this->errors = [];

        if (empty($this->email) || empty($this->password)) {
            $this->errors[] = t('admin.login_required');
            return;
        }

        $auth = app()->make(AuthManager::class);

        if ($auth->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->redirect('/admin');
        } else {
            $this->errors[] = t('admin.login_failed');
        }
    }

    public function render(): Element
    {
        $wrapper = Element::make('div')
            ->class('admin-login', 'min-h-screen', 'bg-gray-50', 'flex', 'items-center', 'justify-center');

        $card = Element::make('div')
            ->class('w-full', 'max-w-md', 'bg-white', 'rounded-lg', 'shadow-md', 'border', 'p-8');

        $card->child(
            Element::make('h2')->class('text-2xl', 'font-bold', 'text-center', 'mb-2')->intl('admin.admin_panel', [], '管理后台')
        );
        $card->child(
            Element::make('p')->class('text-sm', 'text-gray-500', 'text-center', 'mb-6')->intl('admin.login_prompt', [], '请登录您的账户')
        );

        $form = Element::make('form')
            ->class('space-y-6')
            ->liveAction('login', 'submit');

        if (!empty($this->errors)) {
            $errorHtml = Element::make('div')
                ->class('bg-red-50', 'border', 'border-red-200', 'text-red-600', 'p-3', 'rounded', 'text-sm');
            foreach ($this->errors as $error) {
                $errorHtml->child(Element::make('p')->text($error));
            }
            $form->child($errorHtml);
        }

        $emailInput = Input::make()
            ->name('email')
            ->label(t('email'))
            ->email()
            ->placeholder('your@email.com')
            ->required()
            ->liveModel('email');

        $passwordInput = Input::make()
            ->name('password')
            ->label(t('password'))
            ->password()
            ->placeholder('••••••••')
            ->required()
            ->liveModel('password');

        $rememberCheckbox = Checkbox::make()
            ->name('remember')
            ->label(t('remember_me'))
            ->liveModel('remember');

        $submitBtn = Button::make()
            ->label(t('login'))
            ->submit()
            ->primary()
            ->block();

        $form->child($emailInput);
        $form->child($passwordInput);
        $form->child($rememberCheckbox);
        $form->child($submitBtn);

        $card->child($form);
        $wrapper->child($card);

        return $wrapper;
    }
}
