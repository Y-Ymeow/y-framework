<?php

declare(strict_types=1);

namespace Admin\Pages;

use Admin\Auth\AuthManager;
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
    public function login(): void
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
        $wrapper = Grid::make()
            ->cols(1)
            ->alignCenter()
            ->class('admin-login', 'min-h-screen', 'bg-gray-50');

        $card = Card::make()
            ->title(t('admin.admin_panel'))
            ->subtitle(t('admin.login_prompt'))
            ->variant('bordered')
            ->class('w-full', 'max-w-md', 'p-8');

        $form = Element::make('form')
            ->class('space-y-6')
            ->attr('method', 'POST');

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
            ->block()
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
