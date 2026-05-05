<?php

declare(strict_types=1);

namespace Admin\Pages;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\UX\UI\Card;
use Framework\UX\UI\Button;
use Framework\UX\Form\Input;
use Framework\UX\Form\Checkbox;
use Framework\UX\Layout\Grid;
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

    public function render(): Element
    {
        $wrapper = Grid::make()
            ->cols(1)
            ->alignCenter()
            ->class('admin-login', 'min-h-screen', 'bg-gray-50');

        $card = Card::make()
            ->title(t('admin.admin_panel'))
            ->subtitle(t('admin.login_prompt'))
            ->variant('bordered')
            ->class('w-full', 'max-w-md', 'p-8');

        // Email Input
        $emailInput = Input::make()
            ->name('email')
            ->label(t('email'))
            ->email()
            ->placeholder('your@email.com')
            ->required()
            ->liveModel('email');

        // Password Input
        $passwordInput = Input::make()
            ->name('password')
            ->label(t('password'))
            ->password()
            ->placeholder('••••••••')
            ->required()
            ->liveModel('password');

        // Remember Checkbox
        $rememberCheckbox = Checkbox::make()
            ->name('remember')
            ->label(t('remember_me'))
            ->liveModel('remember');

        // Submit Button
        $submitBtn = Button::make()
            ->label(t('login'))
            ->submit()
            ->primary()
            ->block()
            ->liveAction('login', 'submit');

        // Form Container
        $form = Element::make('form')
            ->class('space-y-6')
            ->attr('method', 'POST')
            ->liveAction('login', 'submit');

        $form->child($emailInput);
        $form->child($passwordInput);
        $form->child($rememberCheckbox);
        $form->child($submitBtn);

        $card->child($form);
        $wrapper->child($card);

        return $wrapper;
    }
}
