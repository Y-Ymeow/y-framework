<?php

declare(strict_types=1);

namespace Framework\Install;

use Framework\Database\Connection\Manager;
use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
use Framework\Http\Response\RedirectResponse;
use Framework\View\Base\Element;

class InstallController
{
    public function show(): Response
    {
        if (InstallManager::isInstalled()) {
            return new RedirectResponse('/');
        }

        return Response::html($this->renderPage(1, []));
    }

    public function handle(Request $request): Response
    {
        if (InstallManager::isInstalled()) {
            return new RedirectResponse('/');
        }

        $step = (int) ($request->query('step') ?: $request->input('step', '1'));
        $data = $request->input('data', []);

        if ($request->input('test_db') === '1') {
            $result = $this->testConnection($data);
            return Response::html($this->renderPage(3, $data,
                $result === true ? 'success' : 'error',
                $result === true ? '连接成功' : ('连接失败: ' . $result)));
        }

        if ($step === 3) {
            if (empty($data['db_database'])) {
                return Response::html($this->renderPage(3, $data, 'error', '请输入数据库名'));
            }
            $result = $this->testConnection($data);
            if ($result !== true) {
                return Response::html($this->renderPage(3, $data, 'error', '连接失败: ' . $result));
            }
            return Response::html($this->renderPage(4, $data));
        }

        if ($step === 4) {
            if (empty($data['admin_email']) || empty($data['admin_password'])) {
                return Response::html($this->renderPage(4, $data, 'error', '请填写管理员邮箱和密码'));
            }
            if ($data['admin_password'] !== ($data['admin_password_confirm'] ?? '')) {
                return Response::html($this->renderPage(4, $data, 'error', '两次输入的密码不一致'));
            }
            return Response::html($this->doInstall($data));
        }

        return Response::html($this->renderPage($step, $data));
    }

    private function renderPage(int $step, array $data, string $alertType = '', string $alertMessage = ''): Element
    {
        $content = match ($step) {
            1 => $this->stepWelcome(),
            2 => $this->stepRequirements(),
            3 => $this->stepDatabase($data, $alertType, $alertMessage),
            4 => $this->stepSite($data, $alertType, $alertMessage),
            default => $this->stepWelcome(),
        };

        $stepTitles = ['', '许可协议', '环境检测', '数据库配置', '网站设置'];
        $maxStep = 4;

        $stepsBar = Element::make('div')->class('install-steps');
        for ($i = 1; $i <= $maxStep; $i++) {
            $cls = 'install-step';
            if ($i < $step) $cls .= ' done';
            elseif ($i === $step) $cls .= ' active';
            $numLabel = $i < $step ? 'OK' : (string)$i;
            $stepsBar->child(
                Element::make('div')->class($cls)->children(
                    Element::make('span')->class('step-num')->text($numLabel),
                    Element::make('span')->class('step-label')->text($stepTitles[$i])
                )
            );
        }

        $body = Element::make('div')->class('install-body');
        if ($alertMessage) {
            $body->child(
                Element::make('div')->class("alert alert-{$alertType}")->text($alertMessage)
            );
        }
        $body->child($content);

        $card = Element::make('div')->class('install-card')->children(
            Element::make('div')->class('install-header')->children(
                Element::make('h1')->text('Y-Framework 安装向导'),
                Element::make('p')->text('快速搭建您的网站')
            ),
            $stepsBar,
            $body
        );

        $css = $this->getCss();

        $head = Element::make('head')->children(
            Element::make('meta')->attr('charset', 'UTF-8'),
            Element::make('meta')->attr('name', 'viewport')->attr('content', 'width=device-width, initial-scale=1.0'),
            Element::make('title')->text('安装向导 - Y-Framework'),
            Element::make('style')->html($css)
        );

        $page = Element::make('html')->attr('lang', 'zh-CN')->children(
            $head,
            Element::make('body')->child($card)
        );

        return $page;
    }

    private function stepWelcome(): Element
    {
        $license = Element::make('div')->class('license-box')->html(
            "<strong>MIT License</strong><br><br>"
            . "Copyright (c) 2024 Y-Framework<br><br>"
            . "Permission is hereby granted, free of charge, to any person obtaining a copy "
            . "of this software and associated documentation files (the \"Software\"), to deal "
            . "in the Software without restriction, including without limitation the rights "
            . "to use, copy, modify, merge, publish, distribute, sublicense, and/or sell "
            . "copies of the Software, and to permit persons to whom the Software is "
            . "furnished to do so, subject to the following conditions:<br><br>"
            . "The above copyright notice and this permission notice shall be included in all "
            . "copies or substantial portions of the Software.<br><br>"
            . "THE SOFTWARE IS PROVIDED \"AS IS\", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR "
            . "IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, "
            . "FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT."
        );

        $wrapper = Element::make('div');
        $wrapper->child(Element::make('h2')->text('欢迎安装 Y-Framework'));
        $wrapper->child(
            Element::make('p')->class('text-muted')
                ->text('Y-Framework 是一款现代化的 PHP 全栈框架，提供灵活的页面构建、内容管理和主题系统。本向导将帮助您完成系统安装。')
        );
        $wrapper->child($license);

        $wrapper->child(
            Element::make('form')->attr('method', 'POST')->attr('action', '/install?step=2')->child(
                Element::make('div')->class('install-footer')->children(
                    Element::make('div'),
                    Element::make('button')->attr('type', 'submit')->class('btn', 'btn-primary')->text('开始安装')
                )
            )
        );

        return $wrapper;
    }

    private function stepRequirements(): Element
    {
        $checks = InstallManager::checkRequirements();
        $allPassed = true;
        $checkList = Element::make('div')->class('checks');

        foreach ($checks as $check) {
            if (!$check['passed']) $allPassed = false;
            $icon = $check['passed'] ? Element::make('span')->class('check-icon', 'passed')->html('&#10003;')
                                      : Element::make('span')->class('check-icon', 'failed')->html('&#10007;');
            $label = Element::make('span')->text($check['label']);
            if ($check['detail']) {
                $label->child(Element::make('span')->style('color:#6b7280;font-size:12px')->text(" ({$check['detail']})"));
            }
            $checkList->child(
                Element::make('div')->class('check-item')->children($label, $icon)
            );
        }

        $wrapper = Element::make('div');
        $wrapper->child(Element::make('h2')->text('环境检测'));
        $wrapper->child(Element::make('p')->class('text-muted')->text('检查服务器环境是否满足运行要求'));
        $wrapper->child($checkList);

        $form = Element::make('form')->attr('method', 'POST')->attr('action', '/install?step=3');
        $footer = Element::make('div')->class('install-footer');
        $footer->child(
            Element::make('button')->attr('type', 'button')->class('btn', 'btn-secondary')
                ->attr('onclick', "this.closest('form').querySelector('[type=submit]').disabled=false;window.location.reload()")
                ->text('重新检测')
        );
        $footer->child(
            Element::make('button')->attr('type', 'submit')->class('btn', 'btn-primary')
                ->attr('disabled', $allPassed ? null : 'disabled')
                ->text('下一步')
        );
        $form->child($footer);
        $wrapper->child($form);

        return $wrapper;
    }

    private function stepDatabase(array $data, string $alertType, string $alertMessage): Element
    {
        $driver = $data['db_driver'] ?? 'mysql';
        $host = $data['db_host'] ?? '127.0.0.1';
        $port = $data['db_port'] ?? '3306';
        $database = $data['db_database'] ?? '';
        $username = $data['db_username'] ?? '';
        $password = $data['db_password'] ?? '';

        $form = Element::make('form')->attr('method', 'POST')->attr('action', '/install?step=3');

        $hiddenKeys = ['db_driver', 'db_host', 'db_port', 'db_database', 'db_username', 'db_password'];
        foreach ($this->buildHiddenFields($data, $hiddenKeys) as $hf) {
            $form->child($hf);
        }

        $driverSelect = Element::make('select')
            ->attr('id', 'db_driver')
            ->attr('name', 'data[db_driver]')
            ->attr('onchange', 'this.form.submit()');
        foreach (['mysql' => 'MySQL', 'sqlite' => 'SQLite', 'pgsql' => 'PostgreSQL'] as $val => $label) {
            $opt = Element::make('option')->attr('value', $val)->text($label);
            if ($driver === $val) $opt->attr('selected', 'selected');
            $driverSelect->child($opt);
        }

        $form->child(Element::make('div')->class('form-group')->children(
            Element::make('label')->attr('for', 'db_driver')->text('数据库类型'),
            $driverSelect
        ));

        if ($driver !== 'sqlite') {
            $form->child(
                Element::make('div')->class('form-row')->children(
                    Element::make('div')->class('form-group')->children(
                        Element::make('label')->attr('for', 'db_host')->text('主机地址'),
                        Element::make('input')->attr('type', 'text')->attr('id', 'db_host')->attr('name', 'data[db_host]')->attr('value', $host)
                    ),
                    Element::make('div')->class('form-group')->children(
                        Element::make('label')->attr('for', 'db_port')->text('端口'),
                        Element::make('input')->attr('type', 'text')->attr('id', 'db_port')->attr('name', 'data[db_port]')->attr('value', $port)
                    )
                )
            );
            $form->child(Element::make('div')->class('form-group')->children(
                Element::make('label')->attr('for', 'db_database')->text('数据库名'),
                Element::make('input')->attr('type', 'text')->attr('id', 'db_database')->attr('name', 'data[db_database]')->attr('value', $database)
            ));
            $form->child(
                Element::make('div')->class('form-row')->children(
                    Element::make('div')->class('form-group')->children(
                        Element::make('label')->attr('for', 'db_username')->text('用户名'),
                        Element::make('input')->attr('type', 'text')->attr('id', 'db_username')->attr('name', 'data[db_username]')->attr('value', $username)
                    ),
                    Element::make('div')->class('form-group')->children(
                        Element::make('label')->attr('for', 'db_password')->text('密码'),
                        Element::make('input')->attr('type', 'password')->attr('id', 'db_password')->attr('name', 'data[db_password]')->attr('value', $password)
                    )
                )
            );
        }

        $footer = Element::make('div')->class('install-footer');
        $footer->child(
            Element::make('button')->attr('type', 'submit')->class('btn', 'btn-secondary')->attr('name', 'test_db')->attr('value', '1')->text('测试连接')
        );
        $footer->child(
            Element::make('button')->attr('type', 'submit')->class('btn', 'btn-primary')->text('下一步')
        );
        $form->child($footer);

        $wrapper = Element::make('div');
        $wrapper->child(Element::make('h2')->text('数据库配置'));
        $wrapper->child(Element::make('p')->class('text-muted')->text('请输入数据库连接信息'));
        $wrapper->child($form);

        return $wrapper;
    }

    private function stepSite(array $data, string $alertType, string $alertMessage): Element
    {
        $siteName = $data['site_name'] ?? '我的网站';
        $siteUrl = $data['site_url'] ?? 'http://localhost';
        $adminEmail = $data['admin_email'] ?? '';
        $adminUser = $data['admin_user'] ?? 'admin';
        $adminPassword = $data['admin_password'] ?? '';
        $adminPasswordConfirm = $data['admin_password_confirm'] ?? '';

        $form = Element::make('form')->attr('method', 'POST')->attr('action', '/install?step=5');

        $hiddenKeys = ['db_driver', 'db_host', 'db_port', 'db_database', 'db_username', 'db_password',
                       'site_name', 'site_url', 'admin_email', 'admin_user', 'admin_password', 'admin_password_confirm'];
        foreach ($this->buildHiddenFields($data, $hiddenKeys) as $hf) {
            $form->child($hf);
        }

        $form->child(
            Element::make('div')->class('form-row')->children(
                Element::make('div')->class('form-group')->children(
                    Element::make('label')->attr('for', 'site_name')->text('网站名称'),
                    Element::make('input')->attr('type', 'text')->attr('id', 'site_name')->attr('name', 'data[site_name]')->attr('value', $siteName)
                ),
                Element::make('div')->class('form-group')->children(
                    Element::make('label')->attr('for', 'site_url')->text('网站地址'),
                    Element::make('input')->attr('type', 'text')->attr('id', 'site_url')->attr('name', 'data[site_url]')->attr('value', $siteUrl)
                )
            )
        );

        $form->child(Element::make('h2')->style('margin-top:24px')->text('管理员账户'));

        $form->child(
            Element::make('div')->class('form-row')->children(
                Element::make('div')->class('form-group')->children(
                    Element::make('label')->attr('for', 'admin_user')->text('用户名'),
                    Element::make('input')->attr('type', 'text')->attr('id', 'admin_user')->attr('name', 'data[admin_user]')->attr('value', $adminUser)
                ),
                Element::make('div')->class('form-group')->children(
                    Element::make('label')->attr('for', 'admin_email')->text('邮箱'),
                    Element::make('input')->attr('type', 'email')->attr('id', 'admin_email')->attr('name', 'data[admin_email]')->attr('value', $adminEmail)
                )
            )
        );

        $form->child(
            Element::make('div')->class('form-row')->children(
                Element::make('div')->class('form-group')->children(
                    Element::make('label')->attr('for', 'admin_password')->text('密码'),
                    Element::make('input')->attr('type', 'password')->attr('id', 'admin_password')->attr('name', 'data[admin_password]')->attr('value', $adminPassword)
                ),
                Element::make('div')->class('form-group')->children(
                    Element::make('label')->attr('for', 'admin_password_confirm')->text('确认密码'),
                    Element::make('input')->attr('type', 'password')->attr('id', 'admin_password_confirm')->attr('name', 'data[admin_password_confirm]')->attr('value', $adminPasswordConfirm)
                )
            )
        );

        $footer = Element::make('div')->class('install-footer');
        $footer->child(
            Element::make('button')->attr('type', 'submit')->class('btn', 'btn-success')->attr('id', 'install-btn')->text('开始安装')
        );
        $form->child($footer);

        $wrapper = Element::make('div');
        $wrapper->child(Element::make('h2')->text('网站设置'));
        $wrapper->child(Element::make('p')->class('text-muted')->text('请设置网站基本信息和管理员账户'));
        $wrapper->child($form);

        return $wrapper;
    }

    private function doInstall(array $data): Element
    {
        $logs = [];

        try {
            $logs[] = '正在生成应用密钥...';
            $appKey = InstallManager::generateKey();
            $data['app_key'] = $appKey;

            $logs[] = '正在写入配置文件...';
            InstallManager::writeEnv(array_merge($data, [
                'app_name' => $data['site_name'] ?? 'Y-Framework',
                'app_url' => $data['site_url'] ?? 'http://localhost',
                'app_key' => $appKey,
            ]));
            $logs[] = '配置文件写入完成 ✓';

            $logs[] = '正在连接数据库...';
            $driver = $data['db_driver'] ?? 'mysql';
            $manager = new Manager();
            $manager->switchTo('default', [
                'driver' => $driver,
                'host' => $data['db_host'] ?? '127.0.0.1',
                'port' => (int) ($data['db_port'] ?? ($driver === 'pgsql' ? '5432' : '3306')),
                'database' => $data['db_database'] ?? '',
                'username' => $data['db_username'] ?? '',
                'password' => $data['db_password'] ?? '',
            ]);
            $conn = $manager->connection();
            $logs[] = '数据库连接成功 ✓';

            $logs[] = '正在运行数据库迁移...';
            $migrationLogs = InstallManager::runMigrations($manager);
            foreach ($migrationLogs as $mlog) {
                $logs[] = "  迁移: {$mlog}";
            }
            $logs[] = '数据库迁移完成 ✓';

            $logs[] = '正在创建管理员账户...';
            InstallManager::createAdminUser(
                $manager,
                $data['admin_email'] ?? 'admin@example.com',
                $data['admin_password'] ?? 'admin123',
                $data['admin_user'] ?? 'Admin'
            );
            $logs[] = '管理员账户创建完成 ✓';
        } catch (\Throwable $e) {
            $errorMsg = '错误: ' . $e->getMessage();
            $span = Element::make('span')->style('color:#dc2626')->text($errorMsg);
            $logs[] = $span;
        }

        $success = true;
        $progressItems = Element::make('div')->class('install-progress');
        foreach ($logs as $log) {
            if ($log instanceof Element) {
                $progressItems->child(Element::make('div')->class('progress-item', 'fail')->child($log));
                $success = false;
            } else {
                $cls = 'progress-item';
                if (str_contains($log, '✓')) $cls .= ' done';
                $progressItems->child(Element::make('div')->class($cls)->text($log));
            }
        }

        $continueDiv = Element::make('div')->style('text-align:center;margin-top:24px');
        if ($success) {
            $prefix = \Admin\Services\AdminManager::getPrefix() ?: '/admin';
            $continueDiv->child(
                Element::make('a')->attr('href', $prefix)->class('btn', 'btn-primary')->text('进入管理后台')
            );
            $continueDiv->child(
                Element::make('a')->attr('href', '/')->class('btn', 'btn-secondary')->style('margin-left:8px')->text('访问网站')
            );
            $resultTitle = '安装完成';
        } else {
            $continueDiv->child(
                Element::make('a')->attr('href', '/install')->class('btn', 'btn-primary')->text('重新安装')
            );
            $resultTitle = '安装失败';
        }

        $wrapper = Element::make('div');
        $wrapper->child(Element::make('h2')->text($resultTitle));
        $wrapper->child($progressItems);
        $wrapper->child($continueDiv);

        return $wrapper;
    }

    private function testConnection(array $data): string|bool
    {
        try {
            $driver = $data['db_driver'] ?? 'mysql';
            $host = $data['db_host'] ?? '127.0.0.1';
            $port = (int) ($data['db_port'] ?? ($driver === 'pgsql' ? '5432' : '3306'));
            $database = $data['db_database'] ?? '';
            $username = $data['db_username'] ?? '';
            $password = $data['db_password'] ?? '';

            $manager = new Manager();
            $manager->switchTo('test', [
                'driver' => $driver,
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
            ]);
            $conn = $manager->connection();
            $conn->query('SELECT 1');
            return true;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    private function buildHiddenFields(array $data, array $keys): array
    {
        $fields = [];
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $fields[] = Element::make('input')
                    ->attr('type', 'hidden')
                    ->attr('name', "data[{$key}]")
                    ->attr('value', (string)$data[$key]);
            }
        }
        return $fields;
    }

    private function getCss(): string
    {
        return <<<'CSS'
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;background:#f0f2f5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.install-card{width:680px;background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.08);overflow:hidden}
.install-header{padding:32px 32px 0;text-align:center}
.install-header h1{font-size:24px;font-weight:700;color:#1f2937;margin-bottom:4px}
.install-header p{font-size:14px;color:#6b7280;margin-bottom:24px}
.install-steps{display:flex;padding:0 32px;gap:0;margin-bottom:24px}
.install-step{flex:1;text-align:center;padding:12px 8px 10px;font-size:13px;color:#9ca3af;border-bottom:2px solid #e5e7eb;position:relative;display:flex;flex-direction:column;align-items:center;gap:4px}
.install-step.active{color:#2563eb;border-color:#2563eb;font-weight:600}
.install-step.done{color:#16a34a;border-color:#16a34a}
.step-num{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;background:#e5e7eb;color:#fff}
.install-step.active .step-num{background:#2563eb}
.install-step.done .step-num{background:#16a34a}
.install-body{padding:0 32px 24px;min-height:300px}
.install-footer{padding:16px 32px;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;background:#f9fafb}
.alert{padding:12px 16px;border-radius:6px;font-size:14px;margin-bottom:16px}
.alert-error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.btn{display:inline-flex;align-items:center;padding:10px 24px;border-radius:6px;font-size:14px;font-weight:500;border:none;cursor:pointer;text-decoration:none;transition:all .15s}
.btn-primary{background:#2563eb;color:#fff}
.btn-primary:hover{background:#1d4ed8}
.btn-secondary{background:#e5e7eb;color:#374151}
.btn-secondary:hover{background:#d1d5db}
.btn-success{background:#16a34a;color:#fff}
.btn-success:hover{background:#15803d}
.btn:disabled{opacity:.5;cursor:not-allowed}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px}
.form-group input,.form-group select{width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;outline:none;transition:border-color .15s}
.form-group input:focus,.form-group select:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,0.1)}
.form-row{display:flex;gap:12px}
.form-row .form-group{flex:1}
.check-item{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px}
.check-item:last-child{border-bottom:none}
.check-icon{width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700}
.check-icon.passed{background:#dcfce7;color:#16a34a}
.check-icon.failed{background:#fef2f2;color:#dc2626}
.install-progress{padding:24px 0}
.progress-item{padding:6px 0;font-size:13px;color:#6b7280;display:flex;align-items:center;gap:8px}
.progress-item.done{color:#16a34a}
.progress-item.fail{color:#dc2626}
h2{font-size:18px;font-weight:600;color:#1f2937;margin-bottom:16px}
p.text-muted{font-size:13px;color:#6b7280;margin-bottom:16px;line-height:1.6}
.license-box{background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:12px;height:180px;overflow-y:auto;font-size:12px;color:#6b7280;line-height:1.6;margin-bottom:16px}
CSS;
    }
}
