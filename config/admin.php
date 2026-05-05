<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Pages
    |--------------------------------------------------------------------------
    |
    | 定义后台管理系统的特殊页面。key 为页面标识，value 为页面类名。
    | 用户可以在 config/admin.php 中覆盖这些配置。
    |
    | 支持的页面:
    | - dashboard: 仪表盘首页
    | - login: 登录页面
    |
    */
    'pages' => [
        'dashboard' => \Admin\Pages\DashboardPage::class,
        'login' => \Admin\Pages\LoginPage::class,
    ],
];
