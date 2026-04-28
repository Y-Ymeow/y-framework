# 开发者指南 (Developer Guide)

本指南旨在帮助开发者快速掌握 `ymeow/php-framework` 的核心开发工作流，将架构理念转化为实际的代码实现。

## 1. 核心理念回顾

在开始编写代码前，请理解框架的三个核心支柱：
- **路由优先 (Route-First)**: 抛弃传统的 `routes.php` 大文件。路由直接通过 `#[Route]` 属性定义在控制器方法上，代码与入口紧密结合。
- **组件驱动 (Component-Driven)**: 页面由多个 `LiveComponent` 组成。交互不需要写 JS，直接在 PHP 中定义 `#[LiveAction]`，由服务端驱动 DOM 更新。
- **轻量解耦 (Lightweight)**: 通过 Hook 系统 (Action/Filter) 扩展功能，而非通过继承庞大的基类。

---

## 2. 标准开发工作流

### 场景：创建一个带交互的“用户个人资料”页面

#### 第一步：定义路由与控制器
创建一个控制器，使用 Attribute 定义访问路径。

```php
#[Route('/profile')]
#[Middleware(['auth'])]
class ProfileController {
    #[Get('/')]
    public function index(): Response {
        return view('profile.index', ['user' => user()]);
    }
}
```

#### 第二步：创建交互式 LiveComponent
如果你需要页面的一部分能实时更新（例如：修改昵称），创建 `LiveComponent`。

```bash
php bin/console make:component UserProfile
```

实现组件逻辑：
```php
class UserProfile extends LiveComponent {
    public string $nickname;

    public function mount() {
        $this->nickname = user()->nickname;
    }

    #[LiveAction]
    public function updateNickname(): void {
        $user = user();
        $user->nickname = $this->nickname;
        $user->save();
        $this->toast("昵称已更新！");
        $this->refresh('nickname-display');
    }

    public function render(): Element {
        return Container::make()->children(
            Text::p()->children(
                '当前昵称: ', 
                Text::strong($this->nickname)->liveFragment('nickname-display')
            ),
            Input::make()->model('nickname'),
            Button::make()->label('保存')->liveAction('updateNickname')
        );
    }
}
```

#### 第三步：在页面中嵌入组件
在 `profile/index.blade.php` (或对应视图) 中引入：

```html
<div>
    <h1>个人中心</h1>
    <!-- 渲染 LiveComponent -->
    <live-component class="App\Components\UserProfile" />
</div>
```

#### 第四步：处理数据持久化
如果涉及新表，创建迁移：

```bash
php bin/console make:migration add_nickname_to_users users
```

在迁移文件中定义字段并运行 `php bin/console migrate`。

---

## 3. 关键技术要点

### 依赖注入 (DI)
框架使用 `php-di`。你可以在任何控制器或组件的构造函数中注入服务：

```php
public function __construct(private UserRepository $repository) {}
```

### 状态同步 (State Sync)
`LiveComponent` 的 `public` 属性会自动同步到前端。
- **后端修改 $\to$ 前端**: 通过 `patches` 自动更新，或使用 `$this->refresh('fragment')` 更新 HTML 片段。
- **前端修改 $\to$ 后端**: 通过 `data-model` 绑定，在调用 `LiveAction` 时自动提交。

### 全局扩展 (Hooks)
如果你想在所有响应发送前添加一个自定义 Header，无需修改内核：

```php
class ResponseExtension {
    #[HookFilter('response.sending')]
    public function addCustomHeader($response) {
        $response->headers->set('X-Framework-Version', '1.0');
        return $response;
    }
}
```

---

## 4. 性能优化 checklist

在生产环境部署前，请务必执行以下操作：

1. **路由缓存**: `php bin/console route:cache` (消除文件扫描)
2. **组件缓存**: `php bin/console live:cache` (消除反射开销)
3. **前端构建**: `npm run build` (压缩资源)
4. **环境检查**: 确保 `.env` 中的 `APP_DEBUG` 为 `false`。

## 5. 常见问题 (FAQ)

**Q: 如何在组件中触发另一个组件的更新？**
A: 使用 `$this->dispatch('event-name', $data)` 触发全局事件，目标组件通过 `data-on:event-name` 监听并执行相应逻辑。

**Q: 为什么我的 LiveComponent 属性没有更新？**
A: 请检查该属性是否为 `public`。只有 `public` 属性会被序列化并同步至前端。
