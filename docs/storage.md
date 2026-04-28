# 文件存储系统

## 概述

框架提供完整的文件存储解决方案，包括：
- **Storage**: 文件系统操作
- **Asset**: 资源链接生成
- **文件路由**: 静态资源、媒体文件、下载服务

## 配置

在 `config/filesystems.php` 配置存储磁盘：

```php
return [
    'default' => env('FILESYSTEM_DRIVER', 'local'),
    
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'url' => env('APP_URL') . '/storage',
        ],
        
        'uploads' => [
            'driver' => 'local',
            'root' => storage_path('uploads'),
            'url' => env('APP_URL') . '/media',
        ],
        
        'files' => [
            'driver' => 'local',
            'root' => storage_path('files'),
            'url' => env('APP_URL') . '/download',
        ],
    ],
];
```

## Storage 类

### 基本操作

```php
use Framework\Support\Storage;

// 写入文件
Storage::put('logs/app.log', 'Log content');

// 读取文件
$content = Storage::get('logs/app.log');

// 检查文件是否存在
if (Storage::exists('logs/app.log')) {
    // 文件存在
}

// 检查文件不存在
if (Storage::missing('logs/app.log')) {
    // 文件不存在
}

// 删除文件
Storage::delete('logs/app.log');

// 复制文件
Storage::copy('old/path.txt', 'new/path.txt');

// 移动文件
Storage::move('old/path.txt', 'new/path.txt');
```

### 文件信息

```php
// 文件大小（字节）
$size = Storage::size('document.pdf');

// 最后修改时间（时间戳）
$time = Storage::lastModified('document.pdf');

// MIME 类型
$mime = Storage::mimeType('document.pdf');
```

### 目录操作

```php
// 列出文件
$files = Storage::files('uploads');

// 列出目录
$dirs = Storage::directories('uploads');

// 创建目录
Storage::makeDirectory('uploads/images');

// 删除目录
Storage::deleteDirectory('uploads/images');
```

### 使用不同磁盘

```php
// 指定磁盘
Storage::disk('uploads')->put('avatar.jpg', $fileContent);

// 获取磁盘实例
$disk = Storage::disk('files');
$files = $disk->listContents('/');
```

### URL 生成

```php
// 媒体文件 URL
$url = Storage::mediaUrl('images/avatar.jpg');
// → http://localhost:8000/media/images/avatar.jpg

// 下载 URL
$url = Storage::downloadUrl('documents/report.pdf');
// → http://localhost:8000/download/documents/report.pdf

// 流式传输 URL
$url = Storage::streamUrl('videos/intro.mp4');
// → http://localhost:8000/stream/videos/intro.mp4

// 普通存储 URL
$url = Storage::url('data/export.json', 'local');
// → http://localhost:8000/storage/data/export.json
```

## Asset 类

### 生成资源链接

```php
use Framework\Support\Asset;

// CSS 链接
echo Asset::css('css/style.css');
// <link rel="stylesheet" href="http://localhost:8000/assets/css/style.css?v=1234567890">

// JS 链接
echo Asset::js('js/ux.js');
// <script src="http://localhost:8000/assets/js/ux.js?v=1234567890"></script>

// 图片标签
echo Asset::image('images/logo.png', ['alt' => 'Logo', 'class' => 'img-fluid']);
// <img src="..." alt="Logo" class="img-fluid">

// 仅 URL
$url = Asset::url('css/style.css');
// http://localhost:8000/assets/css/style.css?v=1234567890
```

### 版本控制

自动添加文件修改时间作为版本号，解决缓存问题：

```php
// 启用版本控制（默认）
Asset::url('css/style.css', versioned: true);
// → /assets/css/style.css?v=1704067200

// 禁用版本控制
Asset::url('css/style.css', versioned: false);
// → /assets/css/style.css
```

### Manifest 支持

支持 Vite/Webpack 生成的 `manifest.json`：

```php
Asset::setManifestPath(public_path('build/manifest.json'));

// 自动使用 manifest 中的哈希文件名
Asset::url('js/ux.js');
// → /assets/build/assets/app.abc123.js
```

## 辅助函数

```php
// 资源 URL
asset('css/style.css');
// → http://localhost:8000/assets/css/style.css

// 媒体 URL
media_url('images/photo.jpg');
// → http://localhost:8000/media/images/photo.jpg

// 下载 URL
download_url('files/document.pdf');
// → http://localhost:8000/download/files/document.pdf

// 流式传输 URL
stream_url('videos/movie.mp4');
// → http://localhost:8000/stream/videos/movie.mp4

// 路径
public_path('index.php');      // → /path/to/project/public/index.php
storage_path('logs/app.log');  // → /path/to/project/storage/logs/app.log
```

## 文件路由

### /assets/* - 静态资源

用于 CSS、JS、图片等项目资源：

```
/assets/css/style.css     → public/assets/css/style.css
/assets/js/ux.js         → public/assets/js/ux.js
/assets/images/logo.png   → public/assets/images/logo.png
/assets/build/bundle.js   → public/build/bundle.js
```

特性：
- 自动设置 MIME 类型
- 缓存控制头
- 防盗链保护
- ETag 支持

### /media/* - 媒体文件

用于用户上传的图片，支持图片处理：

```
/media/avatar.jpg              → storage/uploads/avatar.jpg
/media/avatar.jpg?w=200        → 缩放到宽度 200px
/media/avatar.jpg?w=200&h=200  → 缩放到 200x200
/media/avatar.jpg?fit=crop     → 裁剪模式
```

### /download/* - 文件下载

强制下载文件：

```
/download/documents/report.pdf  → storage/files/documents/report.pdf
```

特性：
- Content-Disposition: attachment
- 支持断点续传
- Range 请求支持

### /stream/* - 流式传输

用于视频、音频等大文件：

```
/stream/videos/movie.mp4  → storage/files/videos/movie.mp4
```

特性：
- 支持断点续传
- Range 请求
- 多段范围请求
- 流式输出，内存友好

## 大文件下载

`FileDownloadRoute` 支持以下特性：

### 断点续传

```php
// 客户端请求
Range: bytes=0-1023

// 服务器响应
HTTP/1.1 206 Partial Content
Content-Range: bytes 0-1023/10240
Content-Length: 1024
```

### 多段范围

```php
// 客户端请求
Range: bytes=0-99,200-299

// 服务器响应
HTTP/1.1 206 Partial Content
Content-Type: multipart/byteranges; boundary=BOUNDARY-xxx
```

### 缓存控制

```
ETag: "abc123"
Last-Modified: Mon, 01 Jan 2024 00:00:00 GMT
Cache-Control: public, max-age=31536000
```

## 文件上传示例

```php
class UploadController
{
    public function store(Request $request)
    {
        $file = $request->file('avatar');
        
        $path = 'avatars/' . uniqid() . '.' . $file->extension();
        
        Storage::disk('uploads')->put($path, file_get_contents($file));
        
        return [
            'url' => media_url($path),
        ];
    }
}
```
