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

    #[State]
    public string $newPageSlug = '';

    #[State]
    public string $newPageMiddleware = '';

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
            $this->toast('请填写页面名称', 'error');
            $this->refresh('page-content');
            return;
        }

        $generator = new PageGenerator();
        $result = $generator->generate($name, $route, 'blank', [
            'slug' => $slug ?: $name,
            'middleware' => $middleware,
        ]);

        if (!$result['success']) {
            $this->toast($result['error'] ?? '创建失败', 'error');
        } else {
            $this->toast('页面已创建');
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
        if (empty($uid)) {
            logger()->error('saveComponentSettings: no uid', ['params' => $params]);
            return;
        }

        $tree = json_decode($this->componentTreeJson, true);
        if (!is_array($tree)) {
            logger()->error('saveComponentSettings: invalid tree', ['componentTreeJson' => substr($this->componentTreeJson, 0, 200)]);
            return;
        }

        $settings = [];
        $stylesJson = $params['styles_json'] ?? '';
        foreach ($params as $key => $value) {
            if ($key === 'uid' || $key === 'className' || $key === 'styles_json') continue;
            if (str_starts_with($key, 'style_') || $key === '_custom_classes') continue;
            $settings[$key] = $value;
        }

        if (!empty($stylesJson)) {
            $styles = json_decode($stylesJson, true);
            if (is_array($styles)) {
                $settings['styles'] = array_filter($styles, fn($v) => !empty($v));
            }
        }

        logger()->info('saveComponentSettings', [
            'uid' => $uid,
            'settings' => $settings,
        ]);

        $this->updateAllSettingsInTree($tree, $uid, $settings);
        $this->componentTreeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);

        $generator = new PageGenerator();
        $generator->saveComponentTree($this->editingPage, $tree);

        $this->toast('设置已保存');
        $this->refresh('canvas');
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

    #[LiveAction]
    public function selectMedia(array $params): void
    {
        $url = $params['url'] ?? '';
        $name = $params['name'] ?? '';
        $modalId = $params['modalId'] ?? '';

        if (!$this->selectedUid) return;

        $tree = json_decode($this->componentTreeJson, true);
        if (!is_array($tree)) return;

        $this->updateAllSettingsInTree($tree, $this->selectedUid, [$name => $url]);
        $this->componentTreeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);

        $generator = new PageGenerator();
        $generator->saveComponentTree($this->editingPage, $tree);

        $this->closeModal($modalId);
        $this->refresh('canvas');
        $this->refresh('properties-panel');
    }

    #[LiveAction]
    public function applyLink(array $params): void
    {
        $name = $params['name'] ?? '';
        $modalId = $params['modalId'] ?? '';
        
        // 这里的 params 可能不包含 modal 里的输入值，除非我们在 JS 中处理或使用 data-live-model
        // 目前我们让 JS 更新隐藏 input，我们只需要在这里关闭 modal 并刷新页面
        
        // 如果 params 里有数据（比如通过 JS 传递过来的）
        $url = $params['url'] ?? '';
        $target = $params['target'] ?? '_self';
        $label = $params['label'] ?? '';

        if ($url && $this->selectedUid) {
            $tree = json_decode($this->componentTreeJson, true);
            if (is_array($tree)) {
                $this->updateAllSettingsInTree($tree, $this->selectedUid, [
                    $name => ['url' => $url, 'target' => $target, 'label' => $label]
                ]);
                $this->componentTreeJson = json_encode($tree, JSON_UNESCAPED_UNICODE);
                $generator = new PageGenerator();
                $generator->saveComponentTree($this->editingPage, $tree);
            }
        }

        $this->closeModal($modalId);
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
                $component['settings'] = array_merge($component['settings'] ?? [], $settings);
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
        $wrapper->child($this->renderCreateModal());

        return $wrapper;
    }

    protected function renderCreateModal(): Modal
    {
        $modal = Modal::make()
            ->id('create-page-modal')
            ->title('创建新页面')
            ->size('md');

        $body = Element::make('div')->class('p-4');
        
        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->text('页面名称'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->attr('placeholder', '如 About')
                    ->attr('data-live-model', 'newPageName')
            )
        );

        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->text('Slug'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->attr('placeholder', '如 about-us')
                    ->attr('data-live-model', 'newPageSlug')
            )
        );

        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->text('路由'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->attr('placeholder', '留空使用 /slug')
                    ->attr('data-live-model', 'newPageRoute')
            )
        );

        $body->child(
            Element::make('div')->class('mb-3')->children(
                Element::make('label')->class('form-label')->text('Middleware'),
                Element::make('input')
                    ->class('ux-form-input')
                    ->attr('type', 'text')
                    ->attr('placeholder', '逗号分隔，可选')
                    ->attr('data-live-model', 'newPageMiddleware')
            )
        );

        $modal->content($body);
        $modal->footer(
            Button::make()->label('取消')->secondary()->attr('data-ux-modal-close', 'create-page-modal'),
            Button::make()->label('创建页面')->primary()->attr('data-action:click', 'createPage()')
        );

        return $modal;
    }

    protected function renderPageList(): Element
    {
        $container = Element::make('div')->class('page-list');

        $generator = new PageGenerator();
        $pages = $generator->listPages();

        $headerRow = Element::make('div')->class('page-list-header-row mb-4 d-flex justify-content-between align-items-center');
        $headerRow->child(Element::make('h2')->class('h5 mb-0')->text('现有页面'));
        $headerRow->child(
            Button::make()
                ->label('新建页面')
                ->primary()
                ->attr('data-ux-modal-open', 'create-page-modal')
                ->html('<i class="bi bi-plus-lg"></i> 新建页面')
        );
        $container->child($headerRow);

        if (empty($pages)) {
            $container->child(Element::make('div')->class('page-list-empty')->text('暂无页面，请点击上方按钮创建'));
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
            ->attr('data-page-builder', '');

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
                ->attr('title', '电脑')
                ->html('<i class="bi bi-display"></i>')
        );
        $toolbar->child(
            Element::make('button')
                ->class('page-builder-preview-btn')
                ->attr('data-preview-btn', 'tablet')
                ->attr('title', '平板')
                ->html('<i class="bi bi-tablet"></i>')
        );
        $toolbar->child(
            Element::make('button')
                ->class('page-builder-preview-btn')
                ->attr('data-preview-btn', 'mobile')
                ->attr('title', '手机')
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
            ->attr('data-component-tree', $this->componentTreeJson)
            ->attr('data-preview', 'desktop')
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
            Element::make('button')
                ->class('pb-comp-btn', 'pb-comp-btn-drag')
                ->attr('title', '拖动排序')
                ->html('<i class="bi bi-grip-vertical"></i>')
        );
        $toolbar->child(
            Element::make('button')
                ->class('pb-comp-btn')
                ->attr('data-action:click', 'toggleComponent()')
                ->attr('data-action-params', json_encode(['uid' => $uid], JSON_UNESCAPED_UNICODE))
                ->attr('title', '编辑')
                ->html('<i class="bi bi-pencil"></i>')
        );
        $toolbar->child(
            Element::make('button')
                ->class('pb-comp-btn', 'pb-comp-btn-danger')
                ->attr('data-action:click', 'removeComponent()')
                ->attr('data-action-params', json_encode(['uid' => $uid], JSON_UNESCAPED_UNICODE))
                ->attr('title', '删除')
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
                ->text('内容设置')
        );
        $tabNav->child(
            Element::make('button')
                ->class('page-builder-properties-tab')
                ->attr('data-properties-tab', 'style')
                ->attr('type', 'button')
                ->text('样式设置')
        );

        $contentPanel = Element::make('div')
            ->class('page-builder-properties-tab-content', 'page-builder-properties-tab-content--active')
            ->attr('data-properties-panel', 'content');

        $contentForm = new FormBuilder();
        $contentForm->submitMode();
        $componentType->settings($contentForm);
        $contentForm->fill($component['settings'] ?? []);

        foreach ($contentForm->getComponents() as $formComponent) {
            if (method_exists($formComponent, 'render')) {
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
                    ->attr('placeholder', 'CSS 类名，空格分隔')
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
                ->text('+ 添加')
        );
        $stylePanel->child($addRow);

        $stylePanel->child(
            Element::make('div')
                ->class('page-builder-style-hint')
                ->html('输入 CSS 引擎类名，如 <code>bg-blue-100</code> <code>p-4</code> <code>hover:bg-red-500</code> <code>md:text-lg</code>')
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
