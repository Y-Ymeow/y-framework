<?php

declare(strict_types=1);

namespace Admin\Pages;

use Admin\Contracts\Live\AdminLayout;
use Admin\Contracts\Page\PageInterface;
use Admin\PageBuilder\PageGenerator;
use Admin\PageBuilder\PageBuilderCssService;
use Admin\PageBuilder\Components\ComponentRegistry;
use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\LiveListener;
use Framework\Component\Live\Attribute\State;
use Framework\Component\Live\EmbeddedLiveComponent;
use Framework\UX\Dialog\Modal;
use Framework\View\Base\Element;
use Framework\UX\Form\FormBuilder;
use Framework\UX\UI\Button;

class PageBuilderPage extends LiveComponent implements PageInterface
{
    #[State]
    public string $editingPage = '';

    #[State]
    public string $builderOpen = 'false';

    #[State]
    public string $componentTreeJson = '[]';

    #[State]
    public string $selectedUid = '';

    #[State]
    public string $newPageName = '';

    #[State]
    public string $newPageRoute = '';

    #[State]
    public string $newPageSlug = '';

    #[State]
    public string $newPageMiddleware = '';

    #[State]
    public string $editingPageSettings = '';

    #[State]
    public string $editPageName = '';

    #[State]
    public string $editSlug = '';

    #[State]
    public string $editRoute = '';

    #[State]
    public string $editMiddleware = '';

    public static function getName(): string
    {
        return 'pages';
    }

    public static function getTitle(): string|array
    {
        return ['admin.pages', [], '页面管理'];
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
                'handler' => function () {
                    return static::renderPage();
                },
            ],
        ];
    }

    public static function renderPage()
    {
        $page = new static();
        $page->named('admin-page-pages');

        $layout = new AdminLayout();
        $layout->activeMenu = 'pages';
        $layout->setContent($page);

        return $layout;
    }

    #[LiveAction]
    public function createPage(array $params): void
    {
        $name = trim($params['name'] ?? $this->newPageName);
        $route = trim($params['route'] ?? $this->newPageRoute);
        $slug = trim($params['slug'] ?? $this->newPageSlug);
        $middleware = trim($params['middleware'] ?? $this->newPageMiddleware);

        if (empty($name)) {
            $this->toast('error', t('admin:page_builder.toast.name_required', [], '请填写页面名称'));
            $this->refresh('page-content');
            return;
        }

        $generator = new PageGenerator();
        $result = $generator->generate($name, $route, 'blank', [
            'slug' => $slug ?: $name,
            'middleware' => $middleware,
        ]);

        if (!$result['success']) {
            $this->toast('error', $result['error'] ?? t('admin:page_builder.toast.create_failed', [], '创建失败'));
        } else {
            $this->toast('success', t('admin:page_builder.toast.created', [], '页面已创建'));
            $this->newPageName = '';
            $this->newPageRoute = '';
            $this->newPageSlug = '';
            $this->newPageMiddleware = '';
            $this->closeModal('create-page-modal');
        }

        $this->refresh('page-content');
    }

    #[LiveAction]
    public function deletePage(array $params): void
    {
        $name = $params['name'] ?? '';
        if (empty($name)) return;

        $generator = new PageGenerator();
        $result = $generator->delete($name);

        if ($result['success']) {
            $this->toast('success', t('admin:page_builder.toast.deleted', [], '页面已删除'));
        } else {
            $this->toast('error', $result['error'] ?? t('admin:page_builder.toast.delete_failed', [], '删除失败'));
        }

        $this->refresh('page-content');
    }

    #[LiveAction]
    public function openBuilder(string $name): void
    {
        if (empty($name)) return;

        $this->editingPage = $name;
        $this->builderOpen = 'true';
        $this->selectedUid = '';

        $generator = new PageGenerator();
        $tree = $generator->getComponentTree($name);
        $this->componentTreeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);

        $this->refresh('page-content');
    }

    #[LiveAction]
    public function closeBuilder(): void
    {
        $this->builderOpen = 'false';
        $this->editingPage = '';
        $this->selectedUid = '';
        $this->componentTreeJson = '[]';
        $this->refresh('page-content');
    }

    #[LiveAction]
    public function openPageSettings(string $name): void
    {
        if (empty($name)) return;

        $generator = new PageGenerator();
        $pages = $generator->listPages();

        foreach ($pages as $page) {
            if ($page['name'] === $name) {
                $this->editingPageSettings = $page['name'];
                $this->editPageName = $page['name'];
                $this->editSlug = $page['slug'];
                $this->editRoute = $page['route'];
                $this->editMiddleware = is_array($page['middleware']) ? implode(', ', $page['middleware']) : (string) $page['middleware'];
                break;
            }
        }

        $this->openModal('edit-page-modal');
    }

    #[LiveAction]
    public function savePageSettings(): void
    {
        $name = trim($this->editPageName);
        $slug = trim($this->editSlug);
        $route = trim($this->editRoute);
        $middleware = trim($this->editMiddleware);

        if (empty($name)) {
            $this->toast('error', t('admin:page_builder.toast.empty_name', [], '页面名称不能为空'));
            return;
        }

        $originalName = $this->editingPageSettings;

        $generator = new PageGenerator();
        $result = $generator->updatePage($originalName, [
            'name' => $name,
            'slug' => $slug,
            'route' => $route,
            'middleware' => $middleware,
        ]);

        if ($result['success']) {
            $this->toast('success', t('admin:page_builder.toast.settings_saved', [], '页面设置已保存'));
            $this->closeModal('edit-page-modal');
            $this->editingPageSettings = '';

            // If the builder was open for the renamed page, close it
            if ($this->builderOpen === 'true' && $this->editingPage === $originalName && $originalName !== $name) {
                $this->builderOpen = 'false';
                $this->editingPage = '';
                $this->selectedUid = '';
                $this->componentTreeJson = '[]';
            }
        } else {
            $this->toast('error', $result['error'] ?? t('admin:page_builder.toast.save_failed', [], '保存失败'));
        }

        $this->refresh('page-content');
    }

    #[LiveAction]
    public function saveTree(array $params): void
    {
        $treeJson = $params['tree'] ?? '[]';
        $tree = json_decode($treeJson, true);
        if (!is_array($tree)) {
            $this->toast('error', t('admin:page_builder.toast.invalid_data', [], '无效的组件数据'));
            return;
        }

        $generator = new PageGenerator();
        $result = $generator->saveComponentTree($this->editingPage, $tree);

        if ($result['success']) {
            $this->componentTreeJson = $treeJson;
            $this->toast('success', t('admin:page_builder.toast.saved', [], '已保存'));
        } else {
            $this->toast('error', $result['error'] ?? t('admin:page_builder.toast.save_failed', [], '保存失败'));
        }

        $this->refresh('page-content');
    }

    #[LiveAction]
    public function toggleComponent(mixed $uid = null): void
    {
        $oldUid = $this->selectedUid;
        $this->selectedUid = ($this->selectedUid === $uid) ? '' : $uid;

        if ($oldUid && $oldUid !== $this->selectedUid) {
            $this->refresh('comp-' . $oldUid);
        }
        if ($this->selectedUid) {
            $this->refresh('comp-' . $this->selectedUid);
        }
        $this->refresh('properties-panel');
    }

    #[LiveAction]
    public function removeComponent(array $params): void
    {
        $uid = $params['uid'] ?? '';
        if (empty($uid)) return;

        $tree = json_decode($this->componentTreeJson, true);
        if (!is_array($tree)) return;

        $this->removeFromTree($tree, $uid);
        $this->componentTreeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);

        $generator = new PageGenerator();
        $generator->saveComponentTree($this->editingPage, $tree);

        if ($this->selectedUid === $uid) {
            $this->selectedUid = '';
        }

        $this->refresh('canvas');
        $this->refresh('properties-panel');
    }

    #[LiveListener('fieldChange')]
    public function onFieldChange(array $eventData): void
    {
        if (!$this->selectedUid) return;

        $name = $eventData['name'] ?? '';
        $value = $eventData['value'] ?? '';

        $tree = json_decode($this->componentTreeJson, true);
        if (is_array($tree)) {
            $this->updateAllSettingsInTree($tree, $this->selectedUid, [$name => $value]);
            $this->reconcileNodeSlots($tree, $this->selectedUid);
            $this->componentTreeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);

            $generator = new PageGenerator();
            $generator->saveComponentTree($this->editingPage, $tree);
        }

        $this->refresh('canvas');
        $this->refresh('properties-panel');
    }

    private function updateTreeFromParams(array $params): void
    {
        $uid = $params['uid'] ?? $this->selectedUid;
        if (empty($uid)) return;

        $tree = json_decode($this->componentTreeJson, true);
        if (!is_array($tree)) return;

        $settings = [];
        $stylesJson = $params['styles_json'] ?? '';
        foreach ($params as $key => $value) {
            if (in_array($key, ['uid', 'className', 'styles_json', 'name', 'url', 'modalId', 'target', 'label'])) continue;
            if (str_starts_with($key, 'style_') || $key === '_custom_classes') continue;
            $settings[$key] = $value;
        }

        if (!empty($stylesJson)) {
            $styles = json_decode($stylesJson, true);
            if (is_array($styles)) {
                $settings['styles'] = array_filter($styles, fn($v) => !empty($v));
            }
        }

        $this->updateAllSettingsInTree($tree, $uid, $settings);
        $this->reconcileNodeSlots($tree, $uid);
        $this->componentTreeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);

        $generator = new PageGenerator();
        $generator->saveComponentTree($this->editingPage, $tree);
    }

    #[LiveAction]
    public function saveComponentSettings(array $formData): void
    {
        error_log(json_encode($formData, JSON_UNESCAPED_UNICODE));
        $this->updateTreeFromParams($formData);
        $this->toast('success', t('admin:page_builder.toast.component_saved', [], '设置已保存'));
        $this->refresh('canvas');
        $this->refresh('properties-panel');
    }

    #[LiveAction]
    public function addChildComponent(array $params): void
    {
        $parentUid = $params['parentUid'] ?? '';
        $componentType = $params['componentType'] ?? '';
        $slotName = $params['slotName'] ?? '';
        if (empty($parentUid) || empty($componentType)) return;

        $tree = json_decode($this->componentTreeJson, true);
        if (!is_array($tree)) return;

        if ($slotName) {
            $parent = $this->findInTree($tree, $parentUid);
            if ($parent) {
                $parentTypeObj = ComponentRegistry::get($parent['type'] ?? '');
                if ($parentTypeObj) {
                    $limits = $parentTypeObj->slotLimits($parent['settings'] ?? []);
                    $limit = $limits[$slotName] ?? null;
                    $currentCount = count($parent['slots'][$slotName] ?? []);
                    if ($limit !== null && $currentCount >= $limit) {
                        $this->toast('error', t('admin:page_builder.toast.slot_limit', [], '该位置已达上限'));
                        return;
                    }
                }
            }
        }

        $uid = 'c' . substr(md5(uniqid((string)mt_rand(), true)), 0, 10) ?: 'c' . bin2hex(random_bytes(5));
        $componentTypeObj = ComponentRegistry::get($componentType);
        $defaults = $componentTypeObj ? $componentTypeObj->defaultSettings() : [];

        $newChild = [
            'uid' => $uid,
            'type' => $componentType,
            'settings' => $defaults,
        ];

        $this->addChildToTree($tree, $parentUid, $newChild, $slotName);
        $this->componentTreeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);

        $generator = new PageGenerator();
        $generator->saveComponentTree($this->editingPage, $tree);

        $this->selectedUid = $uid;
        $this->refresh('canvas');
        $this->refresh('properties-panel');
    }

    #[LiveAction]
    public function updateComponentTree(array $params): void
    {
        $treeJson = $params['tree'] ?? '[]';
        $tree = json_decode($treeJson, true);
        if (!is_array($tree)) {
            return;
        }

        if (!$this->validateSlotLimits($tree)) {
            $this->toast('error', t('admin:page_builder.toast.slot_exceeded', [], '拖放后某些插槽超出上限'));
            return;
        }

        $this->componentTreeJson = $treeJson;

        $generator = new PageGenerator();
        $generator->saveComponentTree($this->editingPage, $tree);

        $this->refresh('canvas');
        $this->refresh('properties-panel');
    }

    #[LiveAction]
    public function saveCurrentTree(): void
    {
        $tree = json_decode($this->componentTreeJson, true);
        if (!is_array($tree)) {
            $this->toast('error', t('admin:page_builder.toast.invalid_data', [], '无效的组件数据'));
            return;
        }

        $generator = new PageGenerator();
        $result = $generator->saveComponentTree($this->editingPage, $tree);

        if ($result['success']) {
            $this->toast('success', t('admin:page_builder.toast.tree_saved', [], '页面已保存'));
        } else {
            $this->toast('error', $result['error'] ?? t('admin:page_builder.toast.save_failed', [], '保存失败'));
        }
    }

    private function findInTree(array $tree, string $uid): ?array
    {
        foreach ($tree as $component) {
            if (($component['uid'] ?? '') === $uid) {
                return $component;
            }
            $slots = $component['slots'] ?? [];
            foreach ($slots as $slotItems) {
                $found = $this->findInTree($slotItems, $uid);
                if ($found) return $found;
            }
            $children = $component['children'] ?? [];
            if (!empty($children)) {
                $found = $this->findInTree($children, $uid);
                if ($found) return $found;
            }
        }
        return null;
    }

    private function updateAllSettingsInTree(array &$tree, string $uid, array $settings): bool
    {
        foreach ($tree as &$component) {
            if (($component['uid'] ?? '') === $uid) {
                $component['settings'] = array_merge($component['settings'] ?? [], $settings);
                return true;
            }
            $slots = $component['slots'] ?? [];
            foreach ($slots as $sn => &$slotItems) {
                if ($this->updateAllSettingsInTree($slotItems, $uid, $settings)) {
                    $component['slots'][$sn] = $slotItems;
                    return true;
                }
            }
            $children = $component['children'] ?? [];
            if (!empty($children) && $this->updateAllSettingsInTree($children, $uid, $settings)) {
                $component['children'] = $children;
                return true;
            }
        }
        return false;
    }

    private function removeFromTree(array &$tree, string $uid): bool
    {
        foreach ($tree as $i => $component) {
            if (($component['uid'] ?? '') === $uid) {
                array_splice($tree, $i, 1);
                return true;
            }
            $slots = $component['slots'] ?? [];
            foreach ($slots as $sn => &$slotItems) {
                if ($this->removeFromTree($slotItems, $uid)) {
                    $component['slots'][$sn] = $slotItems;
                    return true;
                }
            }
            $children = $component['children'] ?? [];
            if (!empty($children) && $this->removeFromTree($children, $uid)) {
                $tree[$i]['children'] = $children;
                return true;
            }
        }
        return false;
    }

    private function addChildToTree(array &$tree, string $parentUid, array $newChild, string $slotName = ''): bool
    {
        foreach ($tree as &$component) {
            if (($component['uid'] ?? '') === $parentUid) {
                if ($slotName) {
                    if (!isset($component['slots'])) $component['slots'] = [];
                    if (!isset($component['slots'][$slotName])) $component['slots'][$slotName] = [];
                    $component['slots'][$slotName][] = $newChild;
                } else {
                    if (!isset($component['children'])) $component['children'] = [];
                    $component['children'][] = $newChild;
                }
                return true;
            }
            $slots = $component['slots'] ?? [];
            foreach ($slots as $sn => &$slotItems) {
                if ($this->addChildToTree($slotItems, $parentUid, $newChild, $slotName)) {
                    $component['slots'][$sn] = $slotItems;
                    return true;
                }
            }
            $children = $component['children'] ?? [];
            if (!empty($children) && $this->addChildToTree($children, $parentUid, $newChild, $slotName)) {
                $component['children'] = $children;
                return true;
            }
        }
        return false;
    }

    public function render(): Element
    {
        $wrapper = Element::make('div')->class('page-builder-page');
        $wrapper->child(Element::make('h1')->class('page-builder-title')->intl('admin.pages', [], '页面管理'));

        $content = Element::make('div')->liveFragment('page-content');

        if ($this->builderOpen === 'true') {
            $content->child($this->renderBuilder());
        } else {
            $content->child($this->renderPageList());
        }

        $wrapper->child($content);
        $wrapper->child($this->renderCreateModal());
        $wrapper->child($this->renderEditModal());

        return $wrapper;
    }

    protected function renderCreateModal(): Modal
    {
        $modal = Modal::make()
            ->id('create-page-modal')
            ->title(t('admin:page_builder.create_modal.title', [], '创建新页面'))
            ->size('md');

        $body = Element::make('div')->class('p-4');

        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->intl('admin:page_builder.create_modal.page_name', [], '页面名称'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->intlAttr('placeholder', 'admin:page_builder.create_modal.page_name_placeholder', [], '如 About')
                    ->attr('data-live-model', 'newPageName')
            )
        );

        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->intl('admin:page_builder.create_modal.slug', [], 'Slug'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->intlAttr('placeholder', 'admin:page_builder.create_modal.slug_placeholder', [], '如 about-us')
                    ->attr('data-live-model', 'newPageSlug')
            )
        );

        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->intl('admin:page_builder.create_modal.route', [], '路由'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->intlAttr('placeholder', 'admin:page_builder.create_modal.route_placeholder', [], '留空使用 /slug')
                    ->attr('data-live-model', 'newPageRoute')
            )
        );

        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->intl('admin:page_builder.create_modal.middleware', [], 'Middleware'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->intlAttr('placeholder', 'admin:page_builder.create_modal.middleware_placeholder', [], '逗号分隔，可选')
                    ->attr('data-live-model', 'newPageMiddleware')
            )
        );

        $modal->content($body);
        $modal->footer(
            Button::make()->label(t('admin:page_builder.create_modal.cancel', [], '取消'))->secondary()->attr('data-ux-modal-close', 'create-page-modal'),
            Button::make()->label(t('admin:page_builder.create_modal.create', [], '创建页面'))->primary()->attr('data-action:click', 'createPage()')
        );

        return $modal;
    }

    protected function renderEditModal(): Modal
    {
        $modal = Modal::make()
            ->id('edit-page-modal')
            ->title(t('admin:page_builder.edit_modal.title', [], '编辑页面设置'))
            ->size('md');

        $body = Element::make('div')->class('p-4');

        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->intl('admin:page_builder.edit_modal.page_name', [], '页面名称'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->intlAttr('placeholder', 'admin:page_builder.edit_modal.page_name_placeholder', [], '如 About')
                    ->attr('data-live-model', 'editPageName')
            )
        );

        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->intl('admin:page_builder.edit_modal.slug', [], 'Slug'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->intlAttr('placeholder', 'admin:page_builder.edit_modal.slug_placeholder', [], '如 about-us')
                    ->attr('data-live-model', 'editSlug')
            )
        );

        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->intl('admin:page_builder.edit_modal.route', [], '路由'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->intlAttr('placeholder', 'admin:page_builder.edit_modal.route_placeholder', [], '留空使用 /slug')
                    ->attr('data-live-model', 'editRoute')
            )
        );

        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->intl('admin:page_builder.edit_modal.middleware', [], 'Middleware'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->intlAttr('placeholder', 'admin:page_builder.edit_modal.middleware_placeholder', [], '逗号分隔，可选')
                    ->attr('data-live-model', 'editMiddleware')
            )
        );

        $modal->content($body);
        $modal->footer(
            Button::make()->label(t('admin:page_builder.edit_modal.cancel', [], '取消'))->secondary()->attr('data-ux-modal-close', 'edit-page-modal'),
            Button::make()->label(t('admin:page_builder.edit_modal.save', [], '保存设置'))->primary()->attr('data-action:click', 'savePageSettings()')
        );

        return $modal;
    }

    protected function renderPageList(): Element
    {
        $container = Element::make('div')->class('page-list');

        $generator = new PageGenerator();
        $pages = $generator->listPages();

        $headerRow = Element::make('div')->class('page-list-header-row mb-4 d-flex justify-content-between align-items-center');
        $headerRow->child(Element::make('h2')->class('h5 mb-0')->intl('admin:page_builder.page_list.title', [], '现有页面'));
        $headerRow->child(
            Button::make()
                ->label(t('admin:page_builder.page_list.new_page', [], '新建页面'))
                ->primary()
                ->attr('data-ux-modal-open', 'create-page-modal')
                ->child('<i class="bi bi-plus-lg"></i> ' . t('admin:page_builder.page_list.new_page', [], '新建页面'))
        );
        $container->child($headerRow);

        if (empty($pages)) {
            $container->child(Element::make('div')->class('page-list-empty')->intl('admin:page_builder.page_list.empty', [], '暂无页面，请点击上方按钮创建'));
            return $container;
        }

        $list = Element::make('div')->class('page-list-items');
        foreach ($pages as $page) {
            $item = Element::make('div')->class('page-list-item');
            $info = Element::make('div')->class('page-list-item-info');
            $info->child(Element::make('div')->class('page-list-item-name')->text($page['name']));
            $info->child(Element::make('div')->class('page-list-item-route')->text($page['route'] ?? '/'));
            if (!empty($page['slug'])) {
                $info->child(Element::make('div')->class('page-list-item-route')->text('slug: ' . $page['slug']));
            }
            if (!empty($page['middleware'])) {
                $info->child(Element::make('div')->class('page-list-item-route')->text('middleware: ' . implode(', ', $page['middleware'])));
            }

            $actions = Element::make('div')->class('page-list-item-actions');
            $actions->child(
                Element::make('button')
                    ->class('page-btn', 'page-btn-sm', 'page-btn-outline')
                    ->liveAction('openBuilder', 'click', ['name' => $page['name']])
                    ->html('<i class="bi bi-pencil-square"></i> ' . t('admin:page_builder.page_list.edit', [], '编辑'))
            );
            $actions->child(
                Element::make('button')
                    ->class('page-btn', 'page-btn-sm', 'page-btn-outline')
                    ->liveAction('openPageSettings', 'click', ['name' => $page['name']])
                    ->html('<i class="bi bi-gear"></i>')
            );
            $actions->child(
                Element::make('button')
                    ->class('page-btn', 'page-btn-sm', 'page-btn-danger')
                    ->liveAction('deletePage', 'click', ['name' => $page['name']])
                    ->html('<i class="bi bi-trash3"></i>')
            );

            $item->child($info);
            $item->child($actions);
            $list->child($item);
        }
        $container->child($list);

        return $container;
    }

    protected function renderBuilder(): Element
    {
        $builder = Element::make('div')
            ->class('page-builder')
            ->attr('data-page-builder', '');

        $header = Element::make('div')->class('page-builder-header');
        $header->child(
            Element::make('button')
                ->class('page-builder-close')
                ->attr('data-action:click', 'closeBuilder()')
                ->html('<i class="bi bi-x-lg"></i>')
        );
        $header->child(Element::make('div')->class('page-builder-title-bar')->intl('admin:page_builder.builder.edit_prefix', ['name' => $this->editingPage], '编辑: {name}'));
        $header->child(
            Element::make('button')
                ->class('page-btn', 'page-btn-primary', 'page-btn-sm')
                ->attr('data-action:click', 'saveCurrentTree()')
                ->intl('admin:page_builder.builder.save', [], '保存')
        );
        $builder->child($header);

        $body = Element::make('div')->class('page-builder-body');
        $body->child($this->renderComponentPanel());

        $canvasArea = Element::make('div')->class('page-builder-canvas-area');
        $canvasArea->child($this->renderCanvasToolbar());

        $canvasWrapper = Element::make('div')->class('page-builder-canvas-wrapper');
        $canvasWrapper->child($this->renderCanvas());
        $canvasArea->child($canvasWrapper);

        $body->child($canvasArea);

        $body->child($this->renderPropertiesPanel());
        $builder->child($body);

        return $builder;
    }

    protected function renderComponentPanel(): Element
    {
        $panel = Element::make('div')->class('page-builder-components');

        $categories = ComponentRegistry::byCategory();
        $catLabels = ComponentRegistry::categories();

        foreach ($catLabels as $catKey => $catLabel) {
            $types = $categories[$catKey] ?? [];
            if (empty($types)) continue;

            $group = Element::make('div')->class('page-builder-component-group');
            $group->child(Element::make('div')->class('page-builder-component-group-title')->text($catLabel));

            foreach ($types as $type) {
                $isContainer = method_exists($type, 'isContainer') && $type->isContainer();
                $item = Element::make('div')
                    ->class('page-builder-component-item', $isContainer ? 'is-container' : '')
                    ->attr('data-component-type', $type->name())
                    ->attr('draggable', 'true');
                $item->child(Element::make('i')->class('bi', 'bi-' . $type->icon()));
                $item->child(Element::make('span')->text($type->label()));
                if ($isContainer) {
                    $item->child(Element::make('i')->class('bi', 'bi-layer-stack', 'page-builder-container-badge'));
                }
                $group->child($item);
            }

            $panel->child($group);
        }

        return $panel;
    }

    protected function renderCanvasToolbar(): Element
    {
        $toolbar = Element::make('div')->class('page-builder-canvas-toolbar');

        $toolbar->child(
            Element::make('button')
                ->class('page-builder-preview-btn', 'active')
                ->attr('data-preview-btn', 'desktop')
                ->intlAttr('title', 'admin:page_builder.builder.desktop', [], '电脑')
                ->html('<i class="bi bi-display"></i>')
        );
        $toolbar->child(
            Element::make('button')
                ->class('page-builder-preview-btn')
                ->attr('data-preview-btn', 'tablet')
                ->intlAttr('title', 'admin:page_builder.builder.tablet', [], '平板')
                ->html('<i class="bi bi-tablet"></i>')
        );
        $toolbar->child(
            Element::make('button')
                ->class('page-builder-preview-btn')
                ->attr('data-preview-btn', 'mobile')
                ->intlAttr('title', 'admin:page_builder.builder.mobile', [], '手机')
                ->html('<i class="bi bi-phone"></i>')
        );

        $toolbar->child(Element::make('div')->class('page-builder-toolbar-separator'));

        $toolbar->child(
            Element::make('button')
                ->class('page-builder-zoom-btn')
                ->attr('data-zoom-out', '')
                ->html('<i class="bi bi-dash"></i>')
        );
        $toolbar->child(
            Element::make('span')
                ->class('page-builder-zoom-label')
                ->attr('data-zoom-label', '')
                ->intl('admin:page_builder.builder.zoom', [], '100%')
        );
        $toolbar->child(
            Element::make('button')
                ->class('page-builder-zoom-btn')
                ->attr('data-zoom-in', '')
                ->html('<i class="bi bi-plus"></i>')
        );
        $toolbar->child(
            Element::make('button')
                ->class('page-builder-zoom-btn')
                ->attr('data-zoom-fit', '')
                ->html('<i class="bi bi-arrows-fullscreen"></i>')
        );
        return $toolbar;
    }

    protected function renderCanvas(): Element
    {
        $canvas = Element::make('div')
            ->class('page-builder-canvas')
            ->attr('data-builder-canvas', '')
            ->attr('data-component-tree', $this->componentTreeJson)
            ->attr('data-preview', 'desktop')
            ->liveFragment('canvas');

        $tree = json_decode($this->componentTreeJson, true);
        if (empty($tree) || !is_array($tree)) {
            $canvas->child(Element::make('div')->class('page-builder-canvas-empty')->intl('admin:page_builder.builder.canvas_empty', [], '拖拽左侧组件到这里'));
            return $canvas;
        }

        $cssService = new PageBuilderCssService();
        $dynamicCss = $cssService->generateForTree($tree);
        if ($dynamicCss) {
            $canvas->child(Element::make('style')->html($dynamicCss));
        }

        $this->renderCanvasItems($canvas, $tree);

        return $canvas;
    }

    protected function renderCanvasItems(Element $parent, array $items): void
    {
        foreach ($items as $item) {
            $parent->child($this->renderCanvasItem($item));
        }
    }

    protected function renderCanvasItem(array $item): Element
    {
        $uid = $item['uid'] ?? '';
        $type = $item['type'] ?? '';
        $settings = $item['settings'] ?? [];
        $children = $item['children'] ?? [];
        $slots = $item['slots'] ?? null;

        $componentType = ComponentRegistry::get($type);
        $isSelected = $uid === $this->selectedUid;
        $isContainer = $componentType && method_exists($componentType, 'isContainer') && $componentType->isContainer();
        $slotDefs = $componentType ? $componentType->slots($settings) : [];
        $hasSlots = !empty($slotDefs);

        $comp = Element::make('div')
            ->class('pb-comp', $isSelected ? 'pb-comp-selected' : '', ($isContainer || $hasSlots) ? 'pb-comp-container' : '', $hasSlots ? 'pb-comp-has-slots' : '')
            ->attr('data-uid', $uid)
            ->attr('data-component-type', $type)
            ->liveFragment('comp-' . $uid);

        $toolbar = Element::make('div')->class('pb-comp-toolbar');
        $toolbar->child(
            Element::make('button')
                ->class('pb-comp-btn', 'pb-comp-btn-drag')
                ->intlAttr('title', 'admin:page_builder.builder.drag_sort', [], '拖动排序')
                ->html('<i class="bi bi-grip-vertical"></i>')
        );
        $toolbar->child(
            Element::make('button')
                ->class('pb-comp-btn')
                ->liveAction('toggleComponent', 'click', ['uid' => $uid])
                ->intlAttr('title', 'admin:page_builder.builder.edit', [], '编辑')
                ->html('<i class="bi bi-pencil"></i>')
        );
        $toolbar->child(
            Element::make('button')
                ->class('pb-comp-btn', 'pb-comp-btn-danger')
                ->liveAction('removeComponent', 'click', ['uid' => $uid])
                ->intlAttr('title', 'admin:page_builder.builder.delete', [], '删除')
                ->html('<i class="bi bi-trash3"></i>')
        );
        $comp->child($toolbar);

        if ($componentType) {
            $preview = $componentType->render($settings);
            $preview->class('pb-comp-preview');
            $comp->child($preview);
        }

        if ($hasSlots) {
            $limits = $componentType->slotLimits($settings);
            $slotChildren = $slots ?? [];

            foreach ($slotDefs as $slotDef) {
                $slotName = $slotDef['name'];
                $slotLabel = $slotDef['label'];
                $slotItems = $slotChildren[$slotName] ?? [];
                $limit = $limits[$slotName] ?? null;
                $isFull = $limit !== null && count($slotItems) >= $limit;

                $targetEl = $componentType->getSlotElement($preview, $slotName);

                if (!empty($slotItems)) {
                    foreach ($slotItems as $childItem) {
                        $targetEl->child($this->renderCanvasItem($childItem));
                    }
                }

                if ($isSelected) {
                    if (!$isFull) {
                        $targetEl->child($this->renderAddChildBar($uid, $slotName));
                    }
                }
            }
        } elseif (!empty($children)) {
            $childArea = Element::make('div')
                ->class('pb-comp-children')
                ->attr('data-builder-canvas', '');

            $this->renderCanvasItems($childArea, $children);

            if ($isSelected) {
                $childArea->child($this->renderAddChildBar($uid));
            }

            $comp->child($childArea);
        }

        return $comp;
    }

    protected function renderPropertiesPanel(): Element
    {
        $panel = Element::make('div')
            ->class('page-builder-properties')
            ->liveFragment('properties-panel');

        if (empty($this->selectedUid)) {
            $panel->child(
                Element::make('div')->class('page-builder-properties-empty')
                    ->child(Element::make('i')->class('bi', 'bi-cursor'))
                    ->child(Element::make('p')->intl('admin:page_builder.properties.empty', [], '选择组件以编辑属性'))
            );
            return $panel;
        }

        $tree = json_decode($this->componentTreeJson, true);
        if (!is_array($tree)) {
            $panel->child(
                Element::make('div')->class('page-builder-properties-empty')->intl('admin:page_builder.properties.empty', [], '选择组件以编辑属性')
            );
            return $panel;
        }

        $component = $this->findInTree($tree, $this->selectedUid);
        if (!$component) {
            $panel->child(
                Element::make('div')->class('page-builder-properties-empty')->intl('admin:page_builder.properties.empty', [], '选择组件以编辑属性')
            );
            return $panel;
        }

        $componentType = ComponentRegistry::get($component['type'] ?? '');
        if (!$componentType) {
            $panel->child(
                Element::make('div')->class('page-builder-properties-empty')->intl('admin:page_builder.properties.unknown', [], '未知组件类型')
            );
            return $panel;
        }

        $panelHeader = Element::make('div')->class('page-builder-properties-header');
        $panelHeader->child(Element::make('i')->class('bi', 'bi-' . $componentType->icon()));
        $panelHeader->child(Element::make('span')->text($componentType->label()));
        $panelHeader->child(
            Element::make('button')
                ->class('page-builder-properties-close')
                ->liveAction('toggleComponent', 'click', ['uid' => $this->selectedUid])
                ->html('<i class="bi bi-x"></i>')
        );
        $panel->child($panelHeader);

        $hiddenUid = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('data-submit-field', 'uid')
            ->attr('value', $this->selectedUid);

        $tabNav = Element::make('div')->class('page-builder-properties-tabs');
        $tabNav->child(
            Element::make('button')
                ->class('page-builder-properties-tab', 'page-builder-properties-tab--active')
                ->attr('data-properties-tab', 'content')
                ->attr('type', 'button')
                ->intl('admin:page_builder.properties.content_tab', [], '内容设置')
        );
        $tabNav->child(
            Element::make('button')
                ->class('page-builder-properties-tab')
                ->attr('data-properties-tab', 'style')
                ->attr('type', 'button')
                ->intl('admin:page_builder.properties.style_tab', [], '样式设置')
        );

        $contentPanel = Element::make('div')
            ->class('page-builder-properties-tab-content', 'page-builder-properties-tab-content--active')
            ->attr('data-properties-panel', 'content');

        $contentForm = new FormBuilder();
        $contentForm->submitMode();
        $componentType->settings($contentForm);
        $contentForm->fill($component['settings'] ?? []);

        foreach ($contentForm->getComponents() as $formComponent) {
            if (EmbeddedLiveComponent::isLiveComponent($formComponent)) {
                $formComponent->setParent($this);
                $formComponent->_invoke();
                $contentPanel->child(
                    Element::make('div')->html($formComponent->toHtml())
                );
            } elseif (method_exists($formComponent, 'render')) {
                $contentPanel->child($formComponent->render());
            }
        }

        $stylePanel = Element::make('div')
            ->class('page-builder-properties-tab-content')
            ->attr('data-properties-panel', 'style');

        $currentStyles = $component['settings']['styles'] ?? [];
        $className = $component['settings']['className'] ?? '';
        if (empty($currentStyles) && !empty($className)) {
            $currentStyles = ['root' => $className];
        }

        $styleTargets = $componentType->styleTargets();

        $stylePanel->child(
            Element::make('input')
                ->attr('type', 'hidden')
                ->attr('data-submit-field', 'styles_json')
                ->attr('value', json_encode($currentStyles, JSON_UNESCAPED_UNICODE))
        );

        $stylePanel->child(
            Element::make('div')->class('page-builder-style-editor')->attr('data-style-editor', '')
        );

        foreach ($currentStyles as $target => $classes) {
            if (empty($classes)) continue;
            $targetLabel = $styleTargets[$target] ?? $target;

            $row = Element::make('div')->class('page-builder-style-row');
            $rowHeader = Element::make('div')->class('page-builder-style-row-header');
            $rowHeader->child(
                Element::make('span')->class('page-builder-style-target')->text($targetLabel)
            );
            $rowHeader->child(
                Element::make('button')
                    ->class('page-builder-style-remove')
                    ->attr('type', 'button')
                    ->attr('data-style-remove', $target)
                    ->html('<i class="bi bi-x"></i>')
            );
            $row->child($rowHeader);
            $row->child(
                Element::make('textarea')
                    ->class('ux-form-input', 'page-builder-style-classes')
                    ->attr('data-style-target', $target)
                    ->attr('rows', '2')
                    ->intlAttr('placeholder', 'admin:page_builder.properties.style_placeholder', [], 'CSS 类名，空格分隔')
                    ->text($classes)
            );
            $stylePanel->child($row);
        }

        $addRow = Element::make('div')->class('page-builder-style-add');
        $select = Element::make('select')
            ->class('ux-form-input', 'page-builder-style-target-select')
            ->attr('data-style-target-select', '');
        foreach ($styleTargets as $key => $label) {
            $select->child(
                Element::make('option')
                    ->attr('value', $key)
                    ->text($label)
            );
        }
        $addRow->child($select);
        $addRow->child(
            Element::make('button')
                ->class('page-btn', 'page-btn-sm', 'page-btn-outline')
                ->attr('type', 'button')
                ->attr('data-style-add', '')
                ->intl('admin:page_builder.properties.add_style', [], '+ 添加')
        );
        $stylePanel->child($addRow);

        $stylePanel->child(
            Element::make('div')
                ->class('page-builder-style-hint')
                ->html(t('admin:page_builder.properties.style_hint', [], '输入 CSS 引擎类名，如 <code>bg-blue-100</code> <code>p-4</code> <code>hover:bg-red-500</code> <code>md:text-lg</code>'))
        );

        $panelBody = Element::make('div')->class('page-builder-properties-body');
        $panelBody->child($hiddenUid);
        $panelBody->child($tabNav);
        $panelBody->child($contentPanel);
        $panelBody->child($stylePanel);
        $panel->child($panelBody);

        $panelFooter = Element::make('div')->class('page-builder-properties-footer');
        $panelFooter->child(
            Element::make('button')
                ->class('page-btn', 'page-btn-primary', 'page-btn-sm')
                ->attr('data-submit:click', 'saveComponentSettings')
                ->intl('admin:page_builder.properties.save', [], '保存设置')
        );
        $panel->child($panelFooter);

        return $panel;
    }

    protected function renderAddChildBar(string $parentUid, string $slotName = ''): Element
    {
        $bar = Element::make('div')->class('pb-comp-add-child');

        $categories = ComponentRegistry::byCategory();
        $catLabels = ComponentRegistry::categories();

        foreach ($catLabels as $catKey => $catLabel) {
            $types = $categories[$catKey] ?? [];
            if (empty($types)) continue;

            $group = Element::make('div')->class('pb-comp-add-child-group');
            $group->child(Element::make('span')->class('pb-comp-add-child-label')->text($catLabel));

            foreach ($types as $type) {
                $isContainer = method_exists($type, 'isContainer') && $type->isContainer();
                $actionParams = ['parentUid' => $parentUid, 'componentType' => $type->name()];
                if ($slotName) {
                    $actionParams['slotName'] = $slotName;
                }
                $btn = Element::make('button')
                    ->class('pb-comp-add-child-btn', $isContainer ? 'is-container' : '')
                    ->liveAction('addChildComponent', 'click', $actionParams)
                    ->html('<i class="bi bi-' . $type->icon() . '"></i> ' . $type->label());
                $group->child($btn);
            }

            $bar->child($group);
        }

        return $bar;
    }

    protected function validateSlotLimits(array $tree): bool
    {
        foreach ($tree as $component) {
            $slots = $component['slots'] ?? [];
            if (!empty($slots)) {
                $componentType = ComponentRegistry::get($component['type'] ?? '');
                if ($componentType) {
                    $limits = $componentType->slotLimits($component['settings'] ?? []);
                    foreach ($limits as $slotName => $limit) {
                        if ($limit !== null) {
                            $count = count($slots[$slotName] ?? []);
                            if ($count > $limit) {
                                return false;
                            }
                        }
                    }
                }
            }
            $children = $component['children'] ?? [];
            if (!empty($children) && !$this->validateSlotLimits($children)) {
                return false;
            }
            foreach ($slots as $slotItems) {
                if (!empty($slotItems) && !$this->validateSlotLimits($slotItems)) {
                    return false;
                }
            }
        }
        return true;
    }

    protected function reconcileNodeSlots(array &$tree, string $uid): void
    {
        foreach ($tree as &$component) {
            if (($component['uid'] ?? '') === $uid) {
                $componentType = ComponentRegistry::get($component['type'] ?? '');
                if (!$componentType) return;

                $newSlotDefs = $componentType->slots($component['settings'] ?? []);
                $newSlotNames = array_map(fn($s) => $s['name'], $newSlotDefs);
                $currentSlots = $component['slots'] ?? [];

                $reconciled = [];
                foreach ($newSlotNames as $name) {
                    $reconciled[$name] = $currentSlots[$name] ?? [];
                }
                $component['slots'] = $reconciled;
                return;
            }
            $slots = $component['slots'] ?? [];
            foreach ($slots as &$slotItems) {
                $this->reconcileNodeSlots($slotItems, $uid);
            }
            $children = $component['children'] ?? [];
            if (!empty($children)) {
                $this->reconcileNodeSlots($children, $uid);
            }
        }
    }
}
