# Admin 后台管理 — 开发文档

> 由 DocGen 自动生成于 2026-05-02 05:37:00

## 组件清单

| 名称 | 命名空间 | 文件 | 类型 |
|---|---|---|---|
| `AdminFormPage` | `Framework\Admin\Live` | `php/src/Admin/Live/AdminFormPage.php` | extends Framework\Component\Live\LiveComponent |
| `AdminLayout` | `Framework\Admin\Live` | `php/src/Admin/Live/AdminLayout.php` | extends Framework\Component\Live\LiveComponent |
| `AdminListPage` | `Framework\Admin\Live` | `php/src/Admin/Live/AdminListPage.php` | extends Framework\Component\Live\LiveComponent |
| `AdminManager` | `Framework\Admin` | `php/src/Admin/AdminManager.php` | class |
| `AdminResource` | `Framework\Admin\Attribute` | `php/src/Admin/Attribute/AdminResource.php` | class |
| `AdminResourceController` | `Framework\Admin` | `php/src/Admin/AdminResourceController.php` | class |
| `AdminServiceProvider` | `Framework\Admin` | `php/src/Admin/AdminServiceProvider.php` | extends Framework\Foundation\ServiceProvider |
| `BasePage` | `Framework\Admin\Page` | `php/src/Admin/Page/BasePage.php` | abstract |
| `BaseResource` | `Framework\Admin\Resource` | `php/src/Admin/Resource/BaseResource.php` | abstract |
| `DashboardPage` | `Framework\Admin\Pages` | `php/src/Admin/Pages/DashboardPage.php` | extends Framework\Component\Live\LiveComponent |
| `LoginPage` | `Framework\Admin\Pages` | `php/src/Admin/Pages/LoginPage.php` | extends Framework\Component\Live\LiveComponent |

---

## 详细实现

### `Framework\Admin\Live\AdminFormPage`

- **文件:** `php/src/Admin/Live/AdminFormPage.php`
- **继承:** `Framework\Component\Live\LiveComponent`

**公开方法 (5)：**

- `mount(): void`
- `save(array $params = []): void`
- `resetForm(): void`
- `render(): Framework\View\Base\Element|string`
- `resource(string $resourceName): Closure`

### `Framework\Admin\Live\AdminLayout`

- **文件:** `php/src/Admin/Live/AdminLayout.php`
- **继承:** `Framework\Component\Live\LiveComponent`

**公开方法 (6)：**

- `setContent(mixed $content): Framework\Admin\Live\AdminLayout`
- `mount(): void`
- `render(): Framework\View\Base\Element|string`
- `toggleSidebar(): void`
- `toggleGroup(?string $id = null, bool $open = false): void`
- `navigate(string $menu): void`

### `Framework\Admin\Live\AdminListPage`

- **文件:** `php/src/Admin/Live/AdminListPage.php`
- **继承:** `Framework\Component\Live\LiveComponent`

**公开方法 (10)：**

- `mount(): void`
- `search(array $params): void`
- `sort(array $params): void`
- `loadPage(array $params): void`
- `loadPerPage(array $params): void`
- `deleteSelected(array $params): void`
- `editRow(array $params): void`
- `deleteRow(array $params): void`
- `render(): Framework\View\Base\Element|string`
- `resource(string $resourceName): Closure`

### `Framework\Admin\AdminManager`

- **文件:** `php/src/Admin/AdminManager.php`

**公开方法 (12)：**

- `registerResource(string $resource): void`
- `registerPage(string $page): void`
- `getResources(): array`
- `getPages(): array`
- `getResource(string $name): ?string`
- `getPage(string $name): ?string`
- `setPrefix(string $prefix): void`
- `getPrefix(): string`
- `bootFromConfig(): void` — 从配置加载 admin 页面
- `registerRoutes(Framework\Routing\Router $router): void`
- `brand(string $title): void`
- `getBrandTitle(): string`

### `Framework\Admin\Attribute\AdminResource`

- **文件:** `php/src/Admin/Attribute/AdminResource.php`

### `Framework\Admin\AdminResourceController`

- **文件:** `php/src/Admin/AdminResourceController.php`

**公开方法 (8)：**

- `dashboard(): Framework\Http\Response`
- `indexUrl(string $resource): string`
- `createUrl(string $resource): string`
- `editUrl(string $resource, mixed $id): string`
- `recordUrl(string $resource, mixed $id): string`
- `deleteUrl(string $resource, mixed $id): string`
- `customUrl(string $resource, string $action): string`
- `customRecordUrl(string $resource, mixed $id, string $action): string`

### `Framework\Admin\AdminServiceProvider`

- **文件:** `php/src/Admin/AdminServiceProvider.php`
- **继承:** `Framework\Foundation\ServiceProvider`

**公开方法 (2)：**

- `register(): void`
- `boot(): void`

### `Framework\Admin\Page\BasePage`

- **文件:** `php/src/Admin/Page/BasePage.php`

**公开方法 (4)：**

- `getName(): string`
- `getTitle(): string`
- `getRoutes(): array`
- `renderPage(): Framework\Http\Response` — 默认的页面渲染处理器

### `Framework\Admin\Resource\BaseResource`

- **文件:** `php/src/Admin/Resource/BaseResource.php`

**公开方法 (33)：**

- `getName(): string`
- `getModel(): string`
- `getTitle(): string`
- `getRoutes(): array`
- `page(string $componentClass, array $props = []): Closure` — 为资源快捷创建自定义页面处理器（自动包裹 AdminLayout）
- `configureForm(Framework\UX\Form\FormBuilder $form): void`
- `configureTable(Framework\UX\Data\DataTable $table): void`
- `getHeader(): mixed`
- `getFooter(): mixed`
- `getLiveActions(): array`
- `getListHeader(): mixed`
- `getListFooter(): mixed`
- `getFormHeader(bool $isEdit, ?object $record = null): mixed`
- `getFormFooter(bool $isEdit, ?object $record = null): mixed`
- `getFormBeforeHeader(bool $isEdit, ?object $record = null): mixed`
- `getFormAfterHeader(bool $isEdit, ?object $record = null): mixed`
- `getFormBeforeForm(bool $isEdit, ?object $record = null): mixed`
- `getFormAfterForm(bool $isEdit, ?object $record = null): mixed`
- `getFormBeforeFooter(bool $isEdit, ?object $record = null): mixed`
- `getFormAfterFooter(bool $isEdit, ?object $record = null): mixed`
- `getListBeforeHeader(): mixed`
- `getListAfterHeader(): mixed`
- `getListBeforeTable(): mixed`
- `getListAfterTable(): mixed`
- `getListBeforeFooter(): mixed`
- `getListAfterFooter(): mixed`
- `onFormCreating(object $record): mixed`
- `onFormUpdating(object $record): mixed`
- `onFormCreated(object $record): mixed`
- `onFormUpdated(object $record): mixed`
- `setRecord(?object $record): void`
- `getRecord(): ?object`
- `fireLifecycleWithReturn(string $hook, array $context = []): mixed`

### `Framework\Admin\Pages\DashboardPage`

- **文件:** `php/src/Admin/Pages/DashboardPage.php`
- **继承:** `Framework\Component\Live\LiveComponent`

**公开方法 (3)：**

- `getName(): string`
- `getTitle(): string`
- `render(): Framework\View\Base\Element|string`

### `Framework\Admin\Pages\LoginPage`

- **文件:** `php/src/Admin/Pages/LoginPage.php`
- **继承:** `Framework\Component\Live\LiveComponent`

**公开方法 (4)：**

- `getName(): string`
- `getTitle(): string`
- `login(): void`
- `render(): Framework\View\Base\Element|string`

