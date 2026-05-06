<?php

declare(strict_types=1);

namespace Database\Seeders;

use Framework\Database\Contracts\ConnectionInterface;

class SeedDemoData
{
    public function run(ConnectionInterface $db): void
    {
        $this->seedUsers($db);
        $this->seedCategories($db);
        $this->seedTags($db);
        $this->seedPosts($db);
        $this->seedMedia($db);
    }

    protected function seedUsers(ConnectionInterface $db): void
    {
        $count = $db->query("SELECT count(*) as cnt FROM users")[0]['cnt'] ?? 0;
        if ($count > 1) return;

        $users = [
            ['name' => 'Admin', 'email' => 'admin@admin.com', 'password' => password_hash('admin123', PASSWORD_BCRYPT), 'role' => 'admin', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => '张三', 'email' => 'zhangsan@example.com', 'password' => password_hash('123456', PASSWORD_BCRYPT), 'role' => 'admin', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => '李四', 'email' => 'lisi@example.com', 'password' => password_hash('123456', PASSWORD_BCRYPT), 'role' => 'editor', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => '王五', 'email' => 'wangwu@example.com', 'password' => password_hash('123456', PASSWORD_BCRYPT), 'role' => 'viewer', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => '赵六', 'email' => 'zhaoliu@example.com', 'password' => password_hash('123456', PASSWORD_BCRYPT), 'role' => 'editor', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Alice', 'email' => 'alice@example.com', 'password' => password_hash('123456', PASSWORD_BCRYPT), 'role' => 'admin', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
        ];

        foreach ($users as $user) {
            $exists = $db->query("SELECT id FROM users WHERE email = ?", [$user['email']]);
            if (empty($exists)) {
                $db->insert('users', $user);
            }
        }
    }

    protected function seedCategories(ConnectionInterface $db): void
    {
        $count = $db->query("SELECT count(*) as cnt FROM categories")[0]['cnt'] ?? 0;
        if ($count > 0) return;

        $categories = [
            ['name' => '技术分享', 'slug' => 'tech', 'description' => '技术相关的文章', 'sort' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => '产品设计', 'slug' => 'design', 'description' => '产品与设计思考', 'sort' => 2, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => '团队动态', 'slug' => 'team', 'description' => '团队新闻与动态', 'sort' => 3, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => '行业观察', 'slug' => 'industry', 'description' => '行业趋势与分析', 'sort' => 4, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => '开源项目', 'slug' => 'opensource', 'description' => '开源项目介绍与贡献', 'sort' => 5, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
        ];

        foreach ($categories as $cat) {
            $db->insert('categories', $cat);
        }
    }

    protected function seedTags(ConnectionInterface $db): void
    {
        $count = $db->query("SELECT count(*) as cnt FROM tags")[0]['cnt'] ?? 0;
        if ($count > 0) return;

        $tags = [
            ['name' => 'PHP', 'slug' => 'php', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'JavaScript', 'slug' => 'javascript', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Vue', 'slug' => 'vue', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'React', 'slug' => 'react', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Laravel', 'slug' => 'laravel', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => '数据库', 'slug' => 'database', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'Docker', 'slug' => 'docker', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'DevOps', 'slug' => 'devops', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => '前端', 'slug' => 'frontend', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => '后端', 'slug' => 'backend', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
        ];

        foreach ($tags as $tag) {
            $db->insert('tags', $tag);
        }
    }

    protected function seedPosts(ConnectionInterface $db): void
    {
        $count = $db->query("SELECT count(*) as cnt FROM posts")[0]['cnt'] ?? 0;
        if ($count > 0) return;

        $now = date('Y-m-d H:i:s');
        $posts = [
            ['user_id' => 1, 'category_id' => 1, 'title' => 'PHP 8.3 新特性详解', 'slug' => 'php-83-new-features', 'excerpt' => 'PHP 8.3 带来了许多令人兴奋的新特性，包括类型化类常量、动态类常量获取等。', 'content' => 'PHP 8.3 是 PHP 语言的最新版本，带来了许多改进和新特性。本文将详细介绍这些变化...', 'status' => 'published', 'type' => 'post', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 1, 'category_id' => 1, 'title' => '使用 SQLite 构建轻量级应用', 'slug' => 'sqlite-lightweight-apps', 'excerpt' => 'SQLite 是一个轻量级的嵌入式数据库，非常适合小型应用和原型开发。', 'content' => 'SQLite 是世界上最广泛部署的数据库引擎...', 'status' => 'published', 'type' => 'post', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 2, 'category_id' => 2, 'title' => '响应式设计的最佳实践', 'slug' => 'responsive-design-best-practices', 'excerpt' => '响应式设计是现代 Web 开发的必备技能，本文分享一些实用的最佳实践。', 'content' => '在移动优先的时代，响应式设计已经成为标配...', 'status' => 'published', 'type' => 'post', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 1, 'category_id' => 5, 'title' => '开源框架架构设计思路', 'slug' => 'opensource-framework-architecture', 'excerpt' => '分享我们在设计开源 PHP 框架时的架构思路和决策过程。', 'content' => '设计一个框架需要考虑很多方面...', 'status' => 'published', 'type' => 'post', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 2, 'category_id' => 1, 'title' => 'JavaScript 异步编程指南', 'slug' => 'js-async-guide', 'excerpt' => '深入理解 JavaScript 的异步编程模型，从回调到 Promise 到 async/await。', 'content' => '异步编程是 JavaScript 的核心特性之一...', 'status' => 'draft', 'type' => 'post', 'published_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 1, 'category_id' => 4, 'title' => '2024 年 Web 开发趋势', 'slug' => 'web-dev-trends-2024', 'excerpt' => '回顾 2024 年 Web 开发领域的重要趋势和技术变革。', 'content' => 'Web 开发领域一直在快速演进...', 'status' => 'published', 'type' => 'post', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 3, 'category_id' => 3, 'title' => '团队协作工具对比', 'slug' => 'team-collaboration-tools', 'excerpt' => '对比市面上主流的团队协作工具，帮你选择最适合的方案。', 'content' => '选择合适的团队协作工具对提升效率至关重要...', 'status' => 'draft', 'type' => 'post', 'published_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 1, 'category_id' => 1, 'title' => 'Docker 容器化部署实践', 'slug' => 'docker-deployment-practice', 'excerpt' => '从零开始学习 Docker 容器化部署，涵盖基础概念和实际操作。', 'content' => 'Docker 已经成为现代应用部署的标准工具...', 'status' => 'published', 'type' => 'post', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($posts as $post) {
            $db->insert('posts', $post);
        }
    }

    protected function seedMedia(ConnectionInterface $db): void
    {
        $count = $db->query("SELECT count(*) as cnt FROM media")[0]['cnt'] ?? 0;
        if ($count > 0) return;

        $now = date('Y-m-d H:i:s');
        $media = [
            ['user_id' => 1, 'disk' => 'local', 'path' => 'uploads/2024/01/banner.jpg', 'filename' => 'banner.jpg', 'extension' => 'jpg', 'mime_type' => 'image/jpeg', 'size' => 245760, 'alt' => '网站横幅', 'title' => '首页横幅图片', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 1, 'disk' => 'local', 'path' => 'uploads/2024/01/logo.png', 'filename' => 'logo.png', 'extension' => 'png', 'mime_type' => 'image/png', 'size' => 32768, 'alt' => 'Logo', 'title' => '公司 Logo', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 2, 'disk' => 'local', 'path' => 'uploads/2024/02/avatar-default.png', 'filename' => 'avatar-default.png', 'extension' => 'png', 'mime_type' => 'image/png', 'size' => 16384, 'alt' => '默认头像', 'title' => '默认用户头像', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 1, 'disk' => 'local', 'path' => 'uploads/2024/02/product-demo.mp4', 'filename' => 'product-demo.mp4', 'extension' => 'mp4', 'mime_type' => 'video/mp4', 'size' => 15728640, 'alt' => '产品演示', 'title' => '产品功能演示视频', 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => 2, 'disk' => 'local', 'path' => 'uploads/2024/03/document.pdf', 'filename' => 'document.pdf', 'extension' => 'pdf', 'mime_type' => 'application/pdf', 'size' => 524288, 'alt' => '技术文档', 'title' => 'API 技术文档', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($media as $item) {
            $db->insert('media', $item);
        }
    }
}
