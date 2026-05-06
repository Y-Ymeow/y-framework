<?php

namespace Admin\Settings;

class MailOptions
{
    public static function registerOptions(): void
    {
        OptionsRegistry::register('mail_driver', [
            'label' => ['admin.settings.mail_driver', [], '邮件驱动'],
            'type' => 'select',
            'group' => ['admin.settings.mail', [], '邮件'],
            'options' => [
                'smtp' => 'SMTP',
                'sendmail' => 'Sendmail',
                'log' => 'Log',
                'array' => 'Array',
            ],
            'default' => 'log',
            'description' => ['admin.settings.mail_driver_desc', [], '选择邮件发送方式'],
        ]);

        OptionsRegistry::register('mail_host', [
            'label' => ['admin.settings.mail_host', [], 'SMTP 主机'],
            'type' => 'text',
            'group' => ['admin.settings.mail', [], '邮件'],
            'default' => 'smtp.mailtrap.io',
            'description' => ['admin.settings.mail_host_desc', [], 'SMTP 服务器地址'],
        ]);

        OptionsRegistry::register('mail_port', [
            'label' => ['admin.settings.mail_port', [], 'SMTP 端口'],
            'type' => 'number',
            'group' => ['admin.settings.mail', [], '邮件'],
            'default' => '587',
            'description' => ['admin.settings.mail_port_desc', [], 'SMTP 服务器端口'],
        ]);

        OptionsRegistry::register('mail_username', [
            'label' => ['admin.settings.mail_username', [], 'SMTP 用户名'],
            'type' => 'text',
            'group' => ['admin.settings.mail', [], '邮件'],
            'default' => '',
            'description' => ['admin.settings.mail_username_desc', [], 'SMTP 认证用户名'],
        ]);

        OptionsRegistry::register('mail_password', [
            'label' => ['admin.settings.mail_password', [], 'SMTP 密码'],
            'type' => 'password',
            'group' => ['admin.settings.mail', [], '邮件'],
            'default' => '',
            'description' => ['admin.settings.mail_password_desc', [], 'SMTP 认证密码'],
        ]);

        OptionsRegistry::register('mail_from_address', [
            'label' => ['admin.settings.mail_from_address', [], '发件地址'],
            'type' => 'email',
            'group' => ['admin.settings.mail', [], '邮件'],
            'default' => 'noreply@example.com',
            'description' => ['admin.settings.mail_from_address_desc', [], '默认发件人邮箱地址'],
        ]);

        OptionsRegistry::register('mail_from_name', [
            'label' => ['admin.settings.mail_from_name', [], '发件人名称'],
            'type' => 'text',
            'group' => ['admin.settings.mail', [], '邮件'],
            'default' => config('app.name', 'Admin'),
            'description' => ['admin.settings.mail_from_name_desc', [], '默认发件人显示名称'],
        ]);

        OptionsRegistry::register('mail_encryption', [
            'label' => ['admin.settings.mail_encryption', [], '加密方式'],
            'type' => 'select',
            'group' => ['admin.settings.mail', [], '邮件'],
            'options' => [
                'tls' => 'TLS',
                'ssl' => 'SSL',
                '' => t('admin.settings.none'),
            ],
            'default' => 'tls',
            'description' => ['admin.settings.mail_encryption_desc', [], 'SMTP 连接加密方式'],
        ]);
    }
}
