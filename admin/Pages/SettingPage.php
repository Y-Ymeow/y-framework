<?php

namespace Admin\Pages;

use Admin\Contracts\Live\AdminLayout;
use Admin\Contracts\Page\PageInterface;
use Admin\Settings\OptionsRegistry;
use Framework\Http\Middleware\AdminAuthenticate;
use Framework\Routing\Attribute\Middleware;
use Framework\Routing\Attribute\Route;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Navigation\Tabs;
use Framework\View\Base\Element;

class SettingPage implements PageInterface
{
    public static function getName(): string
    {
        return 'settings';
    }

    public static function getTitle(): string
    {
        return t('admin.settings.title');
    }

    public static function getIcon(): string
    {
        return 'gear';
    }

    public static function getGroup(): string
    {
        return 'admin.system';
    }

    public static function getSort(): int
    {
        return 55;
    }

    public static function getRoutes(): array
    {
        return [
            'admin.settings' => [
                'method' => 'GET',
                'path' => '/settings',
                'handler' => [static::class, '__invoke'],
            ],
        ];
    }

    #[Route(path: '/admin/settings', methods: ['GET', 'POST'])]
    #[Middleware(AdminAuthenticate::class)]
    public function __invoke()
    {
        if (request()->isMethod('POST')) {
            $data = request()->all();
            unset($data['_token']);
            OptionsRegistry::update($data);
            $content = $this->showSettings(true);
        } else {
            $content = $this->showSettings();
        }

        $layout = new AdminLayout();
        $layout->activeMenu = 'settings';
        $layout->setContent($content);

        return $layout;
    }

    protected function showSettings(bool $saved = false): Element
    {
        $groups = OptionsRegistry::getGroups();
        $values = OptionsRegistry::getAll();

        $wrapper = Element::make('div')->class('admin-form-wrapper', 'max-w-4xl', 'mx-auto');
        $wrapper->child(Element::make('h1')
            ->class('text-2xl', 'font-bold', 'mb-6')
            ->intl('admin.settings.title'));

        if ($saved) {
            $alert = Element::make('div')
                ->class('admin-form-success', 'mb-6')
                ->intl('admin.settings.saved');
            $wrapper->child($alert);
        }

        $tabs = Tabs::make();

        foreach ($groups as $group) {
            $definitions = OptionsRegistry::getDefinitionsByGroup($group);
            $tabContent = $this->renderGroupForm($group, $definitions, $values);
            $tabId = 'settings-tab-' . md5($group);
            $tabs->item($group, $tabContent, $tabId);
        }

        $wrapper->child($tabs);

        return $wrapper;
    }

    protected function renderGroupForm(string $group, array $definitions, array $values): Element
    {
        $form = FormBuilder::make()
            ->post()
            ->action(request()->url())
            ->columns(2)
            ->submitLabel(t('admin.settings.save'));

        foreach ($definitions as $key => $field) {
            $value = $values[$key] ?? $field['default'] ?? '';
            $options = [
                'value' => (string)(is_bool($value) ? ($value ? '1' : '0') : $value),
                'help' => $field['description'] ?? $field['help'] ?? '',
            ];
            if (!empty($field['required'])) {
                $options['required'] = true;
            }
            if (!empty($field['dependsOn'])) {
                $depends = [];
                foreach ($field['dependsOn'] as $depKey => $depVal) {
                    $depends[] = $depKey . '=' . (is_bool($depVal) ? ($depVal ? '1' : '0') : $depVal);
                }
                $options['data-depends'] = implode(',', $depends);
            }

            switch ($field['type']) {
                case 'select':
                    $form->select($key, $field['label'], $options, $field['options'] ?? []);
                    break;
                case 'textarea':
                    $form->textarea($key, $field['label'], $options);
                    break;
                case 'password':
                    $form->password($key, $field['label'], $options);
                    break;
                case 'email':
                    $form->email($key, $field['label'], $options);
                    break;
                case 'number':
                    $form->number($key, $field['label'], $options);
                    break;
                case 'checkbox':
                    $options['checked'] = (bool)$value;
                    $form->checkbox($key, $field['label'], $options);
                    break;
                case 'switch':
                    $options['checked'] = (bool)$value;
                    $form->checkbox($key, $field['label'], $options);
                    break;
                case 'file':
                    $form->text($key, $field['label'], $options);
                    break;
                case 'color':
                    $form->text($key, $field['label'], $options);
                    break;
                default:
                    $form->text($key, $field['label'], $options);
                    break;
            }
        }

        return Element::make('div')->html((string)$form);
    }
}
