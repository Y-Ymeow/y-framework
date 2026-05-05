<?php

namespace Admin\Pages;

use Admin\Contracts\Live\AdminLayout;
use Admin\Contracts\Page\PageInterface;
use Admin\PageBuilder\PageGenerator;
use Admin\PageBuilder\ComponentGenerator;
use Framework\Http\Middleware\AdminAuthenticate;
use Framework\Routing\Attribute\Middleware;
use Framework\Routing\Attribute\Route;
use Framework\View\Base\Element;
use Framework\UX\Navigation\Tabs;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\UX\UI\Button;

class PageBuilderPage implements PageInterface
{
    public static function getName(): string
    {
        return 'pages';
    }

    public static function getTitle(): string
    {
        return t('admin.pages');
    }

    public static function getIcon(): string
    {
        return 'file-earmark';
    }

    public static function getGroup(): string
    {
        return '';
    }

    public static function getSort(): int
    {
        return 30;
    }

    public static function getRoutes(): array
    {
        return [
            'admin.pages' => [
                'method' => 'GET',
                'path' => '/pages',
                'handler' => [static::class, '__invoke'],
            ],
        ];
    }

    #[Route(path: '/admin/pages', methods: ['GET', 'POST'])]
    #[Middleware(AdminAuthenticate::class)]
    public function __invoke()
    {
        $request = request();

        if ($request->isMethod('POST')) {
            $action = $request->input('action', '');
            $result = $this->handleAction($action, $request->all());

            $content = $this->showPage($result);
        } else {
            $content = $this->showPage();
        }

        $layout = new AdminLayout();
        $layout->activeMenu = 'pages';
        $layout->setContent($content);

        return $layout;
    }

    protected function handleAction(string $action, array $data): array
    {
        return match ($action) {
            'create_page' => (new PageGenerator())->generate(
                $data['name'] ?? 'NewPage',
                $data['route'] ?? '/new-page',
                $data['template'] ?? 'blank'
            ),
            'delete_page' => (new PageGenerator())->delete($data['name'] ?? ''),
            'create_component' => (new ComponentGenerator())->generate(
                $data['name'] ?? 'NewComponent',
                $data['description'] ?? ''
            ),
            default => ['success' => false, 'error' => 'Unknown action'],
        };
    }

    protected function showPage(array $result = []): Element
    {
        $wrapper = Element::make('div')->class('p-6', 'max-w-6xl', 'mx-auto');
        $wrapper->child(Element::make('h1')
            ->class('text-2xl', 'font-bold', 'mb-6')
            ->intl('admin.pages'));

        if (!empty($result)) {
            $alertClass = ($result['success'] ?? false) ? 'admin-form-success' : 'admin-form-error';
            $alertText = ($result['success'] ?? false)
                ? ($result['file'] ?? t('admin.success'))
                : ($result['error'] ?? t('admin.error'));
            $wrapper->child(Element::make('div')->class($alertClass, 'mb-6')->text($alertText));
        }

        $tabs = Tabs::make();

        $pagesList = $this->renderPagesList();
        $tabs->item(t('admin.pages'), $pagesList, 'tab-pages');

        $componentsList = $this->renderComponentsList();
        $tabs->item(t('admin.settings.general'), $componentsList, 'tab-components');

        $createForm = $this->renderCreateForm();
        $tabs->item(t('admin.create'), $createForm, 'tab-create');

        $wrapper->child($tabs);

        return $wrapper;
    }

    protected function renderPagesList(): Element
    {
        $pages = (new PageGenerator())->listPages();

        if (empty($pages)) {
            return Element::make('div')->class('text-center', 'py-12', 'text-gray-500')
                ->intl('admin.no_results');
        }

        $table = DataTable::make()
            ->column('name', t('admin.fields.name'))
            ->column('route', 'Route')
            ->column('modified', t('admin.fields.updated_at'))
            ->rows($pages);

        return Element::make('div')->child($table);
    }

    protected function renderComponentsList(): Element
    {
        $components = (new ComponentGenerator())->listComponents();

        if (empty($components)) {
            return Element::make('div')->class('text-center', 'py-12', 'text-gray-500')
                ->intl('admin.no_results');
        }

        $table = DataTable::make()
            ->column('name', t('admin.fields.name'))
            ->column('modified', t('admin.fields.updated_at'))
            ->rows($components);

        return Element::make('div')->child($table);
    }

    protected function renderCreateForm(): Element
    {
        $form = FormBuilder::make()
            ->post()
            ->action('/admin/pages')
            ->columns(2)
            ->hidden('action', 'create_page')
            ->text('name', t('admin.fields.name'), ['required' => true, 'help' => '类名，如 AboutPage'])
            ->text('route', 'Route', ['required' => true, 'help' => '路由路径，如 /about'])
            ->select('template', '模板', [], [
                'blank' => '空白页',
                'list' => '列表页',
                'detail' => '详情页',
                'form' => '表单页',
                'landing' => '着陆页',
            ])
            ->submitLabel(t('admin.create'));

        return Element::make('div')->child($form);
    }
}
