<?php

namespace Admin\Settings;

class SecurityOptions
{
    public static function registerOptions(): void
    {
        OptionsRegistry::register('login_max_attempts', [
            'label' => ['admin.settings.login_max_attempts', [], '最大登录尝试'],
            'type' => 'number',
            'group' => ['admin.settings.security', [], '安全'],
            'default' => '5',
            'description' => ['admin.settings.login_max_attempts_desc', [], '账号锁定前允许的最大登录失败次数'],
        ]);

        OptionsRegistry::register('login_lockout_duration', [
            'label' => ['admin.settings.login_lockout_duration', [], '锁定时长'],
            'type' => 'number',
            'group' => ['admin.settings.security', [], '安全'],
            'default' => '15',
            'description' => ['admin.settings.login_lockout_duration_desc', [], '账号锁定持续时间（分钟）'],
        ]);

        OptionsRegistry::register('enable_captcha', [
            'label' => ['admin.settings.enable_captcha', [], '启用验证码'],
            'type' => 'switch',
            'group' => ['admin.settings.security', [], '安全'],
            'default' => false,
            'description' => ['admin.settings.enable_captcha_desc', [], '在登录页面启用验证码'],
        ]);

        OptionsRegistry::register('password_min_length', [
            'label' => ['admin.settings.password_min_length', [], '密码最小长度'],
            'type' => 'number',
            'group' => ['admin.settings.security', [], '安全'],
            'default' => '8',
            'description' => ['admin.settings.password_min_length_desc', [], '用户密码的最小字符数'],
        ]);

        OptionsRegistry::register('password_require_special', [
            'label' => ['admin.settings.password_require_special', [], '要求特殊字符'],
            'type' => 'switch',
            'group' => ['admin.settings.security', [], '安全'],
            'default' => false,
            'description' => ['admin.settings.password_require_special_desc', [], '密码必须包含特殊字符'],
        ]);
    }
}
