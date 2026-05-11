<?php

declare(strict_types=1);

namespace Admin\Pages;

use Admin\Contracts\Live\AdminLayout;
use Admin\Contracts\Page\PageInterface;
use Admin\Theme\ThemeManager as AdminThemeManager;
use Framework\Events\Hook;
use Framework\Events\ThemeActivatedEvent;
use Framework\Events\ThemeSettingsSavedEvent;
use Framework\Http\Middleware\AdminAuthenticate;
use Framework\Theme\ThemeManager;
use Framework\Routing\Attribute\Middleware;
use Framework\Routing\Attribute\Route;
use Framework\View\Base\Element;

class ThemePage implements PageInterface
{
    public static function getName(): string
    {
        return 'themes';
    }

    public static function getTitle(): string|array
    {
        return ['admin.themes.title', [], '主题管理'];
    }

    public static function getIcon(): string
    {
        return 'palette';
    }

    public static function getGroup(): string
    {
        return 'admin.system';
    }

    public static function getSort(): int
    {
        return 50;
    }

    public static function getRoutes(): array
    {
        return [
            'admin.themes' => [
                'method' => 'GET',
                'path' => '/themes',
                'handler' => [static::class, '__invoke'],
            ],
        ];
    }

    #[Route(path: '/admin/themes', methods: ['GET', 'POST'])]
    #[Middleware(AdminAuthenticate::class)]
    public function __invoke()
    {
        if (request()->isMethod('POST')) {
            $this->handlePost();
        }

        $layout = new AdminLayout();
        $layout->activeMenu = 'themes';
        $layout->setContent($this->showThemes());

        return $layout;
    }

    protected function handlePost(): void
    {
        $action = request()->input('action', '');

        if ($action === 'activate') {
            $theme = request()->input('theme', '');
            if ($theme) {
                AdminThemeManager::setActiveTheme($theme);
                Hook::getInstance()->dispatch(new ThemeActivatedEvent($theme));
            }
        } elseif ($action === 'save_settings') {
            $theme = request()->input('theme', '');
            $settings = request()->input('settings', []);
            if ($theme && is_array($settings)) {
                AdminThemeManager::saveThemeSettings($theme, $settings);
                Hook::getInstance()->dispatch(new ThemeSettingsSavedEvent($theme, $settings));
            }
        }
    }

    protected function showThemes(): Element
    {
        $manager = app(ThemeManager::class);
        $allThemes = $manager->scan();
        $activeThemeName = AdminThemeManager::getActiveTheme();

        $wrapper = Element::make('div')->class('admin-form-wrapper', 'admin-form-width--lg', 'mx-auto');
        $wrapper->child(Element::make('h1')
            ->class('text-2xl', 'font-bold', 'mb-6')
            ->intl('admin.themes.title', [], '主题管理'));

        if (empty($allThemes)) {
            $wrapper->child(Element::make('div')
                ->class('admin-empty-state', 'p-8', 'text-center', 'text-gray-500')
                ->intl('admin.themes.no_themes', [], '暂无可用主题'));
            return $wrapper;
        }

        $list = Element::make('div')->class('space-y-6');

        foreach ($allThemes as $name => $meta) {
            $isActive = $name === $activeThemeName;
            $card = $this->renderThemeCard($manager, $name, $meta, $isActive);
            $list->child($card);
        }

        $wrapper->child($list);
        return $wrapper;
    }

    protected function renderThemeCard(ThemeManager $manager, string $name, array $meta, bool $isActive): Element
    {
        $title = $meta['name'] ?? $name;
        $description = $meta['description'] ?? '';
        $version = $meta['version'] ?? '1.0.0';

        $card = Element::make('div')
            ->class('admin-card', 'p-6', 'rounded-lg', 'border',
                $isActive ? 'border-blue-500 ring-2 ring-blue-100' : 'border-gray-200');

        $header = Element::make('div')->class('flex', 'items-start', 'justify-between', 'mb-4');

        $info = Element::make('div')->class('flex-1');
        $info->child(Element::make('h3')
            ->class('text-lg', 'font-semibold')
            ->text($title));

        $info->child(Element::make('div')
            ->class('flex', 'items-center', 'gap-2', 'mt-1'));

        $info->child(Element::make('span')
            ->class('text-sm', 'text-gray-500')
            ->text('v' . $version));

        if ($isActive) {
            $info->child(Element::make('span')
                ->class('ml-2', 'px-2', 'py-0.5', 'text-xs', 'font-medium', 'bg-blue-100', 'text-blue-700', 'rounded-full')
                ->intl('admin.themes.active', [], '当前使用'));
        }

        if ($description) {
            $info->child(Element::make('p')
                ->class('text-sm', 'text-gray-600', 'mt-2')
                ->text($description));
        }

        $actions = Element::make('div')->class('flex', 'items-center', 'gap-2');

        if (!$isActive) {
            $activateForm = Element::make('form')
                ->attr('method', 'POST')
                ->attr('action', request()->url())
                ->class('inline');

            $activateForm->child(Element::make('input')
                ->attr('type', 'hidden')
                ->attr('name', 'action')
                ->attr('value', 'activate'));
            $activateForm->child(Element::make('input')
                ->attr('type', 'hidden')
                ->attr('name', 'theme')
                ->attr('value', $name));

            $activateForm->child(Element::make('button')
                ->attr('type', 'submit')
                ->class('admin-btn', 'admin-btn-primary', 'admin-btn-sm')
                ->intl('admin.themes.activate', [], '启用'));
            $actions->child($activateForm);
        }

        $header->child($info);
        $header->child($actions);
        $card->child($header);

        $settingsDef = $meta['settings'] ?? [];
        if (!empty($settingsDef) && $isActive) {
            $currentSettings = AdminThemeManager::getThemeSettings($name);
            $settingsForm = $this->renderSettingsForm($name, $settingsDef, $currentSettings);
            $card->child($settingsForm);
        }

        return $card;
    }

    protected function renderSettingsForm(string $themeName, array $settingsDef, array $currentSettings): Element
    {
        $form = Element::make('form')
            ->attr('method', 'POST')
            ->attr('action', request()->url())
            ->class('mt-4', 'pt-4', 'border-t', 'border-gray-100');

        $form->child(Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', 'action')
            ->attr('value', 'save_settings'));
        $form->child(Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', 'theme')
            ->attr('value', $themeName));

        $form->child(Element::make('h4')
            ->class('text-sm', 'font-medium', 'text-gray-700', 'mb-3')
            ->intl('admin.themes.settings', [], '主题设置'));

        foreach ($settingsDef as $key => $def) {
            $label = $def['label'] ?? $key;
            $type = $def['type'] ?? 'text';
            $default = $def['default'] ?? '';
            $value = $currentSettings[$key] ?? $default;

            $fieldWrapper = Element::make('div')->class('mb-3');

            $fieldWrapper->child(Element::make('label')
                ->class('form-label', 'block', 'text-sm', 'font-medium', 'text-gray-700', 'mb-1')
                ->attr('for', 'setting-' . $key)
                ->text($label));

            if ($type === 'color') {
                $fieldWrapper->child(Element::make('input')
                    ->attr('type', 'color')
                    ->attr('id', 'setting-' . $key)
                    ->attr('name', 'settings[' . $key . ']')
                    ->attr('value', $value)
                    ->class('ux-form-input', 'w-16', 'h-10', 'p-1'));
            } elseif ($type === 'switch') {
                $fieldWrapper->child(Element::make('label')->class('flex', 'items-center', 'gap-2')->children(
                    Element::make('input')
                        ->attr('type', 'checkbox')
                        ->attr('id', 'setting-' . $key)
                        ->attr('name', 'settings[' . $key . ']')
                        ->attr('value', '1')
                        ->class('toggle-switch')
                        ->attr($value ? 'checked' : '', 'checked'),
                    Element::make('span')->class('text-sm', 'text-gray-600')
                        ->text($value ? '已开启' : '已关闭')
                ));
            } elseif ($type === 'select' && !empty($def['options'])) {
                $select = Element::make('select')
                    ->attr('id', 'setting-' . $key)
                    ->attr('name', 'settings[' . $key . ']')
                    ->class('ux-form-input');

                foreach ($def['options'] as $option) {
                    $opt = Element::make('option')
                        ->attr('value', $option)
                        ->text($option);
                    if ((string)$option === (string)$value) {
                        $opt->attr('selected', 'selected');
                    }
                    $select->child($opt);
                }
                $fieldWrapper->child($select);
            } else {
                $fieldWrapper->child(Element::make('input')
                    ->attr('type', 'text')
                    ->attr('id', 'setting-' . $key)
                    ->attr('name', 'settings[' . $key . ']')
                    ->attr('value', $value)
                    ->class('ux-form-input'));
            }

            $form->child($fieldWrapper);
        }

        $form->child(Element::make('button')
            ->attr('type', 'submit')
            ->class('admin-btn', 'admin-btn-primary', 'admin-btn-sm', 'mt-2')
            ->intl('admin.themes.save_settings', [], '保存设置'));

        return $form;
    }
}