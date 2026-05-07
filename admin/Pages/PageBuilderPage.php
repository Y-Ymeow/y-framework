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
use Framework\Component\Live\Attribute\State;
use Framework\View\Base\Element;
use Framework\UX\Form\FormBuilder;

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

        if (empty($name) || empty($route)) {
            $this->toast('请填写页面名称和路由', 'error');
            $this->refresh('page-content');
            return;
        }

        $generator = new PageGenerator();
        $result = $generator->generate($name, $route);

        if (!$result['success']) {
            $this->toast($result['error'] ?? '创建失败', 'error');
        } else {
            $this->toast('页面已创建');
            $this->newPageName = '';
            $this->newPageRoute = '';
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
            $this->toast('页面已删除');
        } else {
            $this->toast($result['error'] ?? '删除失败', 'error');
        }

        $this->refresh('page-content');
    }

    #[LiveAction]
    public function openBuilder(array $params): void
    {
        $name = $params['name'] ?? '';
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
    public function saveTree(array $params): void
    {
        $treeJson = $params['tree'] ?? '[]';
        $tree = json_decode($treeJson, true);
        if (!is_array($tree)) {
            $this->toast('无效的组件数据', 'error');
            return;
        }

        $generator = new PageGenerator();
        $result = $generator->saveComponentTree($this->editingPage, $tree);

        if ($result['success']) {
            $this->componentTreeJson = $treeJson;
            $this->toast('已保存');
        } else {
            $this->toast($result['error'] ?? '保存失败', 'error');
        }

        $this->refresh('page-content');
    }

    #[LiveAction]
    public function toggleComponent(array $params): void
    {
        $oldUid = $this->selectedUid;
        $uid = $params['uid'] ?? '';
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
    public function saveComponentSettings(array $params): void
    {
        $uid = $params['uid'] ?? '';
        if (empty($uid)) return;

        $tree = json_decode($this->componentTreeJson, true);
        if (!is_array($tree)) return;

        $settings = [];
        foreach ($params as $key => $value) {
            if ($key === 'uid') continue;
            $settings[$key] = $value;
        }

        $this->updateAllSettingsInTree($tree, $uid, $settings);
        $this->componentTreeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);
        $this->toast('设置已保存');
        $this->refresh('comp-' . $uid);
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

        if ($this->selectedUid === $uid) {
            $this->selectedUid = '';
        }

        $this->refresh('canvas');
        $this->refresh('properties-panel');
    }

    #[LiveAction]
    public function addChildComponent(array $params): void
    {
        $parentUid = $params['parentUid'] ?? '';
        $componentType = $params['componentType'] ?? '';
        if (empty($parentUid) || empty($componentType)) return;

        $tree = json_decode($this->componentTreeJson, true);
        if (!is_array($tree)) return;

        $uid = 'c' . substr(md5(uniqid((string)mt_rand(), true)), 0, 10) ?: 'c' . bin2hex(random_bytes(5));
        $componentTypeObj = ComponentRegistry::get($componentType);
        $defaults = $componentTypeObj ? $componentTypeObj->defaultSettings() : [];

        $newChild = [
            'uid' => $uid,
            'type' => $componentType,
            'settings' => $defaults,
            'children' => [],
        ];

        $this->addChildToTree($tree, $parentUid, $newChild);
        $this->componentTreeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);
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
        $this->componentTreeJson = $treeJson;
        $this->refresh('canvas');
    }

    #[LiveAction]
    public function saveCurrentTree(): void
    {
        $tree = json_decode($this->componentTreeJson, true);
        if (!is_array($tree)) {
            $this->toast('无效的组件数据', 'error');
            return;
        }

        $generator = new PageGenerator();
        $result = $generator->saveComponentTree($this->editingPage, $tree);

        if ($result['success']) {
            $this->toast('页面已保存');
        } else {
            $this->toast($result['error'] ?? '保存失败', 'error');
        }
    }

    private function findInTree(array $tree, string $uid): ?array
    {
        foreach ($tree as $component) {
            if (($component['uid'] ?? '') === $uid) {
                return $component;
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
                $component['settings'] = $settings;
                return true;
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
            $children = $component['children'] ?? [];
            if (!empty($children) && $this->removeFromTree($children, $uid)) {
                $tree[$i]['children'] = $children;
                return true;
            }
        }
        return false;
    }

    private function addChildToTree(array &$tree, string $parentUid, array $newChild): bool
    {
        foreach ($tree as &$component) {
            if (($component['uid'] ?? '') === $parentUid) {
                if (!isset($component['children'])) $component['children'] = [];
                $component['children'][] = $newChild;
                return true;
            }
            $children = $component['children'] ?? [];
            if (!empty($children) && $this->addChildToTree($children, $parentUid, $newChild)) {
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

        return $wrapper;
    }

    protected function renderPageList(): Element
    {
        $container = Element::make('div')->class('page-list');

        $generator = new PageGenerator();
        $pages = $generator->listPages();

        $createRow = Element::make('div')->class('page-create-row');
        $createRow->child(
            Element::make('input')
                ->class('page-input')
                ->attr('type', 'text')
                ->attr('placeholder', '页面名称 (如 About)')
                ->attr('data-live-model', 'newPageName')
        );
        $createRow->child(
            Element::make('input')
                ->class('page-input')
                ->attr('type', 'text')
                ->attr('placeholder', '路由 (如 /about)')
                ->attr('data-live-model', 'newPageRoute')
        );
        $createRow->child(
            Element::make('button')
                ->class('page-btn', 'page-btn-primary')
                ->attr('data-action:click', 'createPage()')
                ->text('创建页面')
        );
        $container->child($createRow);

        if (empty($pages)) {
            $container->child(Element::make('div')->class('page-list-empty')->text('暂无页面，请创建'));
            return $container;
        }

        $list = Element::make('div')->class('page-list-items');
        foreach ($pages as $page) {
            $item = Element::make('div')->class('page-list-item');
            $info = Element::make('div')->class('page-list-item-info');
            $info->child(Element::make('div')->class('page-list-item-name')->text($page['name']));
            $info->child(Element::make('div')->class('page-list-item-route')->text($page['route'] ?? '/'));

            $actions = Element::make('div')->class('page-list-item-actions');
            $actions->child(
                Element::make('button')
                    ->class('page-btn', 'page-btn-sm', 'page-btn-outline')
                    ->attr('data-action:click', 'openBuilder()')
                    ->attr('data-action-params', json_encode(['name' => $page['name']], JSON_UNESCAPED_UNICODE))
                    ->html('<i class="bi bi-pencil-square"></i> 编辑')
            );
            $actions->child(
                Element::make('button')
                    ->class('page-btn', 'page-btn-sm', 'page-btn-danger')
                    ->attr('data-action:click', 'deletePage()')
                    ->attr('data-action-params', json_encode(['name' => $page['name']], JSON_UNESCAPED_UNICODE))
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
            ->attr('data-page-builder', '')
            ->attr('data-component-tree', $this->componentTreeJson);

        $header = Element::make('div')->class('page-builder-header');
        $header->child(
            Element::make('button')
                ->class('page-builder-close')
                ->attr('data-action:click', 'closeBuilder()')
                ->html('<i class="bi bi-x-lg"></i>')
        );
        $header->child(Element::make('div')->class('page-builder-title-bar')->text('编辑: ' . $this->editingPage));
        $header->child(
            Element::make('button')
                ->class('page-btn', 'page-btn-primary', 'page-btn-sm')
                ->attr('data-action:click', 'saveCurrentTree()')
                ->text('保存')
        );
        $builder->child($header);

        $body = Element::make('div')->class('page-builder-body');
        $body->child($this->renderComponentPanel());

        $canvasArea = Element::make('div')->class('page-builder-canvas-area');
        $canvasArea->child($this->renderCanvasToolbar());
        $canvasArea->child($this->renderCanvas());
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
                ->class('page-builder-zoom-btn')
                ->attr('data-zoom-out', '')
                ->html('<i class="bi bi-dash"></i>')
        );
        $toolbar->child(
            Element::make('span')
                ->class('page-builder-zoom-label')
                ->attr('data-zoom-label', '')
                ->text('100%')
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
            ->liveFragment('canvas');

        $tree = json_decode($this->componentTreeJson, true);
        if (empty($tree) || !is_array($tree)) {
            $canvas->child(Element::make('div')->class('page-builder-canvas-empty')->text('拖拽左侧组件到这里'));
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

        $componentType = ComponentRegistry::get($type);
        $isSelected = $uid === $this->selectedUid;
        $isContainer = $componentType && method_exists($componentType, 'isContainer') && $componentType->isContainer();

        $comp = Element::make('div')
            ->class('pb-comp', $isSelected ? 'pb-comp-selected' : '', $isContainer ? 'pb-comp-container' : '')
            ->attr('data-uid', $uid)
            ->attr('data-component-type', $type)
            ->liveFragment('comp-' . $uid);

        $toolbar = Element::make('div')->class('pb-comp-toolbar');
        $toolbar->child(
            Element::make('span')->class('pb-comp-label')
                ->html('<i class="bi bi-' . ($componentType?->icon() ?? 'square') . '"></i> ' . ($componentType?->label() ?? $type))
        );
        if ($isContainer) {
            $toolbar->child(Element::make('span')->class('pb-comp-badge')->text('容器'));
        }
        $toolbar->child(Element::make('div')->class('pb-comp-toolbar-actions'));
        $toolbar->child(
            Element::make('button')
                ->class('pb-comp-btn')
                ->attr('data-action:click', 'toggleComponent()')
                ->attr('data-action-params', json_encode(['uid' => $uid], JSON_UNESCAPED_UNICODE))
                ->html('<i class="bi bi-pencil"></i>')
        );
        $toolbar->child(
            Element::make('button')
                ->class('pb-comp-btn', 'pb-comp-btn-danger')
                ->attr('data-action:click', 'removeComponent()')
                ->attr('data-action-params', json_encode(['uid' => $uid], JSON_UNESCAPED_UNICODE))
                ->html('<i class="bi bi-trash3"></i>')
        );
        $comp->child($toolbar);

        if ($componentType) {
            $preview = $componentType->render($settings);
            $preview->class('pb-comp-preview');
            $comp->child($preview);
        }

        if ($isContainer) {
            $childArea = Element::make('div')
                ->class('pb-comp-children')
                ->attr('data-builder-canvas', '');

            if (!empty($children)) {
                $this->renderCanvasItems($childArea, $children);
            }

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
                    ->child(Element::make('p')->text('选择组件以编辑属性'))
            );
            return $panel;
        }

        $tree = json_decode($this->componentTreeJson, true);
        if (!is_array($tree)) {
            $panel->child(
                Element::make('div')->class('page-builder-properties-empty')->text('选择组件以编辑属性')
            );
            return $panel;
        }

        $component = $this->findInTree($tree, $this->selectedUid);
        if (!$component) {
            $panel->child(
                Element::make('div')->class('page-builder-properties-empty')->text('选择组件以编辑属性')
            );
            return $panel;
        }

        $componentType = ComponentRegistry::get($component['type'] ?? '');
        if (!$componentType) {
            $panel->child(
                Element::make('div')->class('page-builder-properties-empty')->text('未知组件类型')
            );
            return $panel;
        }

        $panelHeader = Element::make('div')->class('page-builder-properties-header');
        $panelHeader->child(Element::make('i')->class('bi', 'bi-' . $componentType->icon()));
        $panelHeader->child(Element::make('span')->text($componentType->label()));
        $panelHeader->child(
            Element::make('button')
                ->class('page-builder-properties-close')
                ->attr('data-action:click', 'toggleComponent()')
                ->attr('data-action-params', json_encode(['uid' => $this->selectedUid], JSON_UNESCAPED_UNICODE))
                ->html('<i class="bi bi-x"></i>')
        );
        $panel->child($panelHeader);

        $formBody = Element::make('div')->class('page-builder-properties-body');

        $formBody->child(
            Element::make('input')
                ->attr('type', 'hidden')
                ->attr('data-submit-field', 'uid')
                ->attr('value', $this->selectedUid)
        );

        $form = new FormBuilder();
        $componentType->settings($form);
        $form->fill($component['settings'] ?? []);

        foreach ($form->getComponents() as $formComponent) {
            if (method_exists($formComponent, 'render')) {
                $formBody->child($formComponent->render());
            }
        }
        $panel->child($formBody);

        $panelFooter = Element::make('div')->class('page-builder-properties-footer');
        $panelFooter->child(
            Element::make('button')
                ->class('page-btn', 'page-btn-primary', 'page-btn-sm')
                ->attr('data-submit:click', 'saveComponentSettings')
                ->text('保存设置')
        );
        $panel->child($panelFooter);

        return $panel;
    }

    protected function renderAddChildBar(string $parentUid): Element
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
                $btn = Element::make('button')
                    ->class('pb-comp-add-child-btn', $isContainer ? 'is-container' : '')
                    ->attr('data-action:click', 'addChildComponent()')
                    ->attr('data-action-params', json_encode(['parentUid' => $parentUid, 'componentType' => $type->name()], JSON_UNESCAPED_UNICODE))
                    ->html('<i class="bi bi-' . $type->icon() . '"></i> ' . $type->label());
                $group->child($btn);
            }

            $bar->child($group);
        }

        return $bar;
    }
}
