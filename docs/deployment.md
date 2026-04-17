# 部署指南

Y Framework 支持多种部署环境，从现代的 FrankenPHP 到传统的虚拟主机。

## 1. 推荐：FrankenPHP (Worker 模式)

这是性能最高的部署方式，应用常驻内存。

```bash
frankenphp run --config Caddyfile
```

## 2. 传统：Nginx + PHP-FPM

设置 Web 根目录为 `public/` 目录：

```nginx
server {
    listen 80;
    server_name example.com;
    root /path/to/project/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 3. 受限：虚拟主机 (无 public 权限)

如果你无法修改 Web 根目录，框架已经内置了安全机制：

1. 将整个项目上传到 Web 根目录。
2. 根目录下的 `.htaccess` 会自动：
   - 禁止访问 `src/`, `app/`, `vendor/`, `.env` 等敏感目录。
   - 将静态资源请求映射到 `public/`。
   - 将所有路由请求引导至根目录的 `index.php`。

**注意**：在这种环境下，请务必确保 Apache 的 `mod_rewrite` 已开启，且 `.htaccess` 被允许执行 (`AllowOverride All`)。
