<?php

namespace Admin\Pages;

use Admin\Models\PluginSetting;
use Admin\Contracts\Live\AdminLayout;
use Admin\Contracts\Page\PageInterface;
use Framework\Http\Middleware\AdminAuthenticate;
use Framework\Plugin\PluginManager;
use Framework\Routing\Attribute\Middleware;
use Framework\Routing\Attribute\Route;
use Framework\UX\Form\FormBuilder;
use Framework\View\Base\Element;
use Framework\View\Element\Container;

class PluginPage implements PageInterface
{
    public static function getName(): string
    {
        return 'plugins';
    }

    public static function getTitle(): string|array
    {
        return ['admin.plugins.title', [], '插件管理'];
    }

    public static function getIcon(): string
    {
        return 'puzzle';
    }

    public static function getGroup(): string
    {
        return 'admin.system';
    }

    public static function getSort(): int
    {
        return 60;
    }

    public static function getRoutes(): array
    {
        return [
            'admin.plugins' => [
                'method' => 'GET',
                'path' => '/plugins',
                'handler' => [static::class, '__invoke'],
            ],
        ];
    }

    #[Route(path: '/admin/plugins', methods: ['GET', 'POST'])]
    #[Middleware(AdminAuthenticate::class)]
    public function __invoke()
    {
        if (request()->isMethod('POST')) {
            $this->handleToggle();
        }

        $layout = new AdminLayout();
        $layout->activeMenu = 'plugins';
        $layout->setContent($this->showPlugins());

        return $layout;
    }

    protected function handleToggle(): void
    {
        $name = request()->input('name', '');
        $enabled = request()->input('enabled') === '1';

        if (!$name) {
            return;
        }

        $existing = PluginSetting::find($name);

        if ($existing) {
            $existing->enabled = $enabled;
            $existing->save();
        } else {
            $setting = new PluginSetting();
            $setting->name = $name;
            $setting->enabled = $enabled;
            $setting->save();
        }

        \Framework\Events\Hook::fire($enabled ? 'plugin.activated' : 'plugin.deactivated', [$name]);
    }

    protected function showPlugins(): Element
    {
        $manager = app(PluginManager::class);
        $allPlugins = $manager->scan();

        $settings = [];
        foreach (PluginSetting::all() as $s) {
            $settings[$s->name] = $s;
        }

        $wrapper = Element::make('div')->class('admin-form-wrapper', 'admin-form-width--lg', 'mx-auto');
        $wrapper->child(Element::make('h1')
            ->class('text-2xl', 'font-bold', 'mb-6')
            ->intl('admin.plugins.title'));

        if (empty($allPlugins)) {
            $wrapper->child(Element::make('div')
                ->class('admin-empty-state', 'p-8', 'text-center', 'text-gray-500')
                ->intl('admin.plugins.no_plugins'));

            return $wrapper;
        }

        $list = Element::make('div')->class('space-y-4');

        foreach ($allPlugins as $name => $meta) {
            $setting = $settings[$name] ?? null;
            $enabled = $setting ? (bool) $setting->enabled : false;

            $card = $this->renderPluginCard($name, $meta, $enabled);
            $list->child($card);
        }

        $wrapper->child($list);

        return $wrapper;
    }

    protected function renderPluginCard(string $name, array $meta, bool $enabled): Element
    {
        $title = $meta['title'] ?? $name;
        $description = $meta['description'] ?? '';
        $version = $meta['version'] ?? '1.0.0';

        $card = Element::make('div')
            ->class('admin-card', 'p-6', 'rounded-lg', 'border', 'flex', 'items-start', 'justify-between');

        $info = Element::make('div')->class('flex-1', 'mr-4');
        $info->child(Element::make('h3')
            ->class('text-lg', 'font-semibold')
            ->text($title));
        $info->child(Element::make('div')
            ->class('text-sm', 'text-gray-500', 'mt-1')
            ->text('v' . $version));

        if ($description) {
            $info->child(Element::make('p')
                ->class('text-sm', 'text-gray-600', 'mt-2')
                ->text($description));
        }

        $form = Element::make('form')
            ->attr('method', 'POST')
            ->attr('action', request()->url());

        $form->child(Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', 'name')
            ->attr('value', $name));

        $toggleId = 'plugin-toggle-' . $name;
        $checkbox = Element::make('input')
            ->attr('type', 'checkbox')
            ->attr('id', $toggleId)
            ->attr('name', 'enabled')
            ->attr('value', '1')
            ->class('toggle-switch')
            ->attr('onchange', 'this.form.submit()');

        if ($enabled) {
            $checkbox->attr('checked', 'checked');
        }

        $form->child($checkbox);
        $form->child(Element::make('label')
            ->attr('for', $toggleId)
            ->class('ml-2', 'text-sm')
            ->text($enabled ? '已启用' : '已禁用'));

        $card->child($info);
        $card->child($form);

        return $card;
    }
}