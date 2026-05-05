<?php

namespace Admin\Settings;

class SecurityOptions
{
    public static function registerOptions(): void
    {
        OptionsRegistry::register('login_max_attempts', [
            'label' => t('admin.settings.login_max_attempts'),
            'type' => 'number',
            'group' => t('admin.settings.security'),
            'default' => '5',
            'description' => t('admin.settings.login_max_attempts_desc'),
        ]);

        OptionsRegistry::register('login_lockout_duration', [
            'label' => t('admin.settings.login_lockout_duration'),
            'type' => 'number',
            'group' => t('admin.settings.security'),
            'default' => '15',
            'description' => t('admin.settings.login_lockout_duration_desc'),
        ]);

        OptionsRegistry::register('enable_captcha', [
            'label' => t('admin.settings.enable_captcha'),
            'type' => 'switch',
            'group' => t('admin.settings.security'),
            'default' => false,
            'description' => t('admin.settings.enable_captcha_desc'),
        ]);

        OptionsRegistry::register('password_min_length', [
            'label' => t('admin.settings.password_min_length'),
            'type' => 'number',
            'group' => t('admin.settings.security'),
            'default' => '8',
            'description' => t('admin.settings.password_min_length_desc'),
        ]);

        OptionsRegistry::register('password_require_special', [
            'label' => t('admin.settings.password_require_special'),
            'type' => 'switch',
            'group' => t('admin.settings.security'),
            'default' => false,
            'description' => t('admin.settings.password_require_special_desc'),
        ]);
    }
}
