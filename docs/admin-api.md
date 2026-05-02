# Admin 后台管理 — API 参考

> 由 DocGen 自动生成于 2026-05-02 05:56:00

## 目录

**其他**
- [`AdminFormPage`](#framework-admin-live-adminformpage)
- [`AdminLayout`](#framework-admin-live-adminlayout)
- [`AdminListPage`](#framework-admin-live-adminlistpage)
- [`AdminManager`](#framework-admin-adminmanager)
- [`AdminResource`](#framework-admin-attribute-adminresource)
- [`AdminResourceController`](#framework-admin-adminresourcecontroller)
- [`AdminServiceProvider`](#framework-admin-adminserviceprovider)
- [`BasePage`](#framework-admin-page-basepage)
- [`BaseResource`](#framework-admin-resource-baseresource)
- [`DashboardPage`](#framework-admin-pages-dashboardpage)
- [`LoginPage`](#framework-admin-pages-loginpage)

---

### 其他

<a name="framework-admin-live-adminformpage"></a>
#### `Framework\Admin\Live\AdminFormPage`

**继承:** `Framework\Component\Live\LiveComponent`  | **实现:** `Stringable`  | **文件:** `php/src/Admin/Live/AdminFormPage.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$resourceName` | `string` = '' |  |
| `$recordId` | `?int` = null |  |
| `$formData` | `array` = [] |  |
| `$formErrors` | `array` = [] |  |
| `$saved` | `bool` = false |  |

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `mount` |  | — |
| `save` |  | `array $params` = [] |
| `resetForm` |  | — |
| `render` |  | — |
| `resource` |  | `string $resourceName` |


<a name="framework-admin-live-adminlayout"></a>
#### `Framework\Admin\Live\AdminLayout`

**继承:** `Framework\Component\Live\LiveComponent`  | **实现:** `Stringable`  | **文件:** `php/src/Admin/Live/AdminLayout.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$activeMenu` | `string` = '' |  |
| `$sidebarCollapsed` | `bool` = false |  |
| `$expandedGroups` | `array` = [] |  |

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `setContent` |  | `mixed $content` |
| `mount` |  | — |
| `render` |  | — |
| `toggleSidebar` |  | — |
| `toggleGroup` |  | `?string $id` = null, `bool $open` = false |
| `navigate` |  | `string $menu` |


<a name="framework-admin-live-adminlistpage"></a>
#### `Framework\Admin\Live\AdminListPage`

**继承:** `Framework\Component\Live\LiveComponent`  | **实现:** `Stringable`  | **文件:** `php/src/Admin/Live/AdminListPage.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$resourceName` | `string` = '' |  |
| `$page` | `int` = 1 |  |
| `$perPage` | `int` = 15 |  |
| `$sortField` | `string` = '' |  |
| `$sortDirection` | `string` = 'asc' |  |
| `$searchQuery` | `string` = '' |  |
| `$selectedKeys` | `array` = [] |  |

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `mount` |  | — |
| `search` |  | `array $params` |
| `sort` |  | `array $params` |
| `loadPage` |  | `array $params` |
| `loadPerPage` |  | `array $params` |
| `deleteSelected` |  | `array $params` |
| `editRow` |  | `array $params` |
| `deleteRow` |  | `array $params` |
| `render` |  | — |
| `resource` |  | `string $resourceName` |


<a name="framework-admin-adminmanager"></a>
#### `Framework\Admin\AdminManager`

**文件:** `php/src/Admin/AdminManager.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `registerResource` |  | `string $resource` |
| `registerPage` |  | `string $page` |
| `getResources` |  | — |
| `getPages` |  | — |
| `getResource` |  | `string $name` |
| `getPage` |  | `string $name` |
| `setPrefix` |  | `string $prefix` |
| `getPrefix` |  | — |
| `bootFromConfig` | 从配置加载 admin 页面 | — |
| `registerRoutes` |  | `Framework\Routing\Router $router` |
| `brand` |  | `string $title` |
| `getBrandTitle` |  | — |


<a name="framework-admin-attribute-adminresource"></a>
#### `Framework\Admin\Attribute\AdminResource`

**文件:** `php/src/Admin/Attribute/AdminResource.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$name` | `string` |  |
| `$model` | `string` |  |
| `$title` | `string` |  |
| `$icon` | `string` |  |
| `$routePrefix` | `?string` |  |
| `$middleware` | `array` |  |
| `$group` | `string` |  |


<a name="framework-admin-adminresourcecontroller"></a>
#### `Framework\Admin\AdminResourceController`

**文件:** `php/src/Admin/AdminResourceController.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `dashboard` |  | — |
| `indexUrl` |  | `string $resource` |
| `createUrl` |  | `string $resource` |
| `editUrl` |  | `string $resource`, `mixed $id` |
| `recordUrl` |  | `string $resource`, `mixed $id` |
| `deleteUrl` |  | `string $resource`, `mixed $id` |
| `customUrl` |  | `string $resource`, `string $action` |
| `customRecordUrl` |  | `string $resource`, `mixed $id`, `string $action` |


<a name="framework-admin-adminserviceprovider"></a>
#### `Framework\Admin\AdminServiceProvider`

**继承:** `Framework\Foundation\ServiceProvider`  | **文件:** `php/src/Admin/AdminServiceProvider.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `register` |  | — |
| `boot` |  | — |


<a name="framework-admin-page-basepage"></a>
#### `Framework\Admin\Page\BasePage`

**实现:** `Framework\Admin\Page\PageInterface`  | **abstract**  | **文件:** `php/src/Admin/Page/BasePage.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getName` |  | — |
| `getTitle` |  | — |
| `getRoutes` |  | — |
| `renderPage` | 默认的页面渲染处理器 | — |


<a name="framework-admin-resource-baseresource"></a>
#### `Framework\Admin\Resource\BaseResource`

**实现:** `Framework\Admin\Resource\ResourceInterface`  | **abstract**  | **文件:** `php/src/Admin/Resource/BaseResource.php`

**常量：**

| 常量 | 值 | 说明 |
|---|---|---|
| `LIFECYCLE_LIST_BEFORE_HEADER` | `'resource.list.before_header'` | 生命周期阶段常量 |
| `LIFECYCLE_LIST_AFTER_HEADER` | `'resource.list.after_header'` |  |
| `LIFECYCLE_LIST_BEFORE_TABLE` | `'resource.list.before_table'` |  |
| `LIFECYCLE_LIST_AFTER_TABLE` | `'resource.list.after_table'` |  |
| `LIFECYCLE_LIST_BEFORE_FOOTER` | `'resource.list.before_footer'` |  |
| `LIFECYCLE_LIST_AFTER_FOOTER` | `'resource.list.after_footer'` |  |
| `LIFECYCLE_FORM_BEFORE_HEADER` | `'resource.form.before_header'` |  |
| `LIFECYCLE_FORM_AFTER_HEADER` | `'resource.form.after_header'` |  |
| `LIFECYCLE_FORM_BEFORE_FORM` | `'resource.form.before_form'` |  |
| `LIFECYCLE_FORM_AFTER_FORM` | `'resource.form.after_form'` |  |
| `LIFECYCLE_FORM_BEFORE_FOOTER` | `'resource.form.before_footer'` |  |
| `LIFECYCLE_FORM_AFTER_FOOTER` | `'resource.form.after_footer'` |  |
| `LIFECYCLE_FORM_CREATING` | `'resource.form.creating'` |  |
| `LIFECYCLE_FORM_UPDATING` | `'resource.form.updating'` |  |
| `LIFECYCLE_FORM_CREATED` | `'resource.form.created'` |  |
| `LIFECYCLE_FORM_UPDATED` | `'resource.form.updated'` |  |
| `LIFECYCLE_TABLE_CONFIGURING` | `'resource.table.configuring'` |  |
| `LIFECYCLE_FORM_CONFIGURING` | `'resource.form.configuring'` |  |

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getName` |  | — |
| `getModel` |  | — |
| `getTitle` |  | — |
| `getRoutes` |  | — |
| `page` | 为资源快捷创建自定义页面处理器（自动包裹 AdminLayout） | `string $componentClass`, `array $props` = [] |
| `configureForm` |  | `Framework\UX\Form\FormBuilder $form` |
| `configureTable` |  | `Framework\UX\Data\DataTable $table` |
| `getHeader` |  | — |
| `getFooter` |  | — |
| `getLiveActions` |  | — |
| `getListHeader` |  | — |
| `getListFooter` |  | — |
| `getFormHeader` |  | `bool $isEdit`, `?object $record` = null |
| `getFormFooter` |  | `bool $isEdit`, `?object $record` = null |
| `getFormBeforeHeader` |  | `bool $isEdit`, `?object $record` = null |
| `getFormAfterHeader` |  | `bool $isEdit`, `?object $record` = null |
| `getFormBeforeForm` |  | `bool $isEdit`, `?object $record` = null |
| `getFormAfterForm` |  | `bool $isEdit`, `?object $record` = null |
| `getFormBeforeFooter` |  | `bool $isEdit`, `?object $record` = null |
| `getFormAfterFooter` |  | `bool $isEdit`, `?object $record` = null |
| `getListBeforeHeader` |  | — |
| `getListAfterHeader` |  | — |
| `getListBeforeTable` |  | — |
| `getListAfterTable` |  | — |
| `getListBeforeFooter` |  | — |
| `getListAfterFooter` |  | — |
| `onFormCreating` |  | `object $record` |
| `onFormUpdating` |  | `object $record` |
| `onFormCreated` |  | `object $record` |
| `onFormUpdated` |  | `object $record` |
| `setRecord` |  | `?object $record` |
| `getRecord` |  | — |
| `fireLifecycleWithReturn` |  | `string $hook`, `array $context` = [] |


<a name="framework-admin-pages-dashboardpage"></a>
#### `Framework\Admin\Pages\DashboardPage`

**继承:** `Framework\Component\Live\LiveComponent`  | **实现:** `Stringable`  | **文件:** `php/src/Admin/Pages/DashboardPage.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getName` |  | — |
| `getTitle` |  | — |
| `render` |  | — |


<a name="framework-admin-pages-loginpage"></a>
#### `Framework\Admin\Pages\LoginPage`

**继承:** `Framework\Component\Live\LiveComponent`  | **实现:** `Stringable`  | **文件:** `php/src/Admin/Pages/LoginPage.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$email` | `string` = '' |  |
| `$password` | `string` = '' |  |
| `$remember` | `bool` = false |  |

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getName` |  | — |
| `getTitle` |  | — |
| `login` |  | — |
| `render` |  | — |


