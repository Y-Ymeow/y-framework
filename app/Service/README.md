# Rich Editor Demo

## 文件说明

- `RichEditorDemo.php` - 富文本编辑器演示页面

## 使用方式

### 1. 作为页面组件使用

在路由中添加：

```php
// routes/web.php 或其他路由文件
use App\Pages\RichEditorDemo;

Route::get('/demo/rich-editor', function() {
    $page = new RichEditorDemo();
    return $page->toHtml();
});
```

### 2. 在视图中嵌入

```php
use App\Pages\RichEditorDemo;

// 在控制器中
public function index()
{
    $editorDemo = new RichEditorDemo();
    return view('my-view', ['editorDemo' => $editorDemo]);
}
```

在 Blade/模板中：
```html
<div class="container">
    {{ $editorDemo }}
</div>
```

### 3. 直接渲染

```php
$demo = new RichEditorDemo();
echo $demo->render();
```

## 演示功能

1. **基础编辑器** - 完整工具栏，支持所有基本格式
2. **简洁模式** - minimal() 模式，隐藏工具栏
3. **PHP 扩展** - 展示 MentionExtension、EmojiExtension、PlaceholderExtension
4. **输出预览** - HTML / Markdown / 纯文本 三种格式输出

## 扩展使用示例

```php
use Framework\UX\Form\RichEditor;
use Framework\UX\RichEditor\Extensions\MentionExtension;
use Framework\UX\RichEditor\Extensions\EmojiExtension;
use Framework\UX\RichEditor\Extensions\PlaceholderExtension;

$editor = RichEditor::make()
    ->name('content')
    ->label('正文')
    ->extension('mention', new MentionExtension('mention', [
        'trigger' => '@',
    ]))
    ->extension('emoji', new EmojiExtension('emoji'))
    ->extension('placeholder', (new PlaceholderExtension('placeholder'))
        ->addPlaceholder('username', '用户名')
    );
```
