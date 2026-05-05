<?php

namespace Admin\Settings;

class MailOptions
{
    public static function registerOptions(): void
    {
        OptionsRegistry::register('mail_driver', [
            'label' => t('admin.settings.mail_driver'),
            'type' => 'select',
            'group' => t('admin.settings.mail'),
            'options' => [
                'smtp' => 'SMTP',
                'sendmail' => 'Sendmail',
                'log' => 'Log',
                'array' => 'Array',
            ],
            'default' => 'log',
            'description' => t('admin.settings.mail_driver_desc'),
        ]);

        OptionsRegistry::register('mail_host', [
            'label' => t('admin.settings.mail_host'),
            'type' => 'text',
            'group' => t('admin.settings.mail'),
            'default' => 'smtp.mailtrap.io',
            'description' => t('admin.settings.mail_host_desc'),
        ]);

        OptionsRegistry::register('mail_port', [
            'label' => t('admin.settings.mail_port'),
            'type' => 'number',
            'group' => t('admin.settings.mail'),
            'default' => '587',
            'description' => t('admin.settings.mail_port_desc'),
        ]);

        OptionsRegistry::register('mail_username', [
            'label' => t('admin.settings.mail_username'),
            'type' => 'text',
            'group' => t('admin.settings.mail'),
            'default' => '',
            'description' => t('admin.settings.mail_username_desc'),
        ]);

        OptionsRegistry::register('mail_password', [
            'label' => t('admin.settings.mail_password'),
            'type' => 'password',
            'group' => t('admin.settings.mail'),
            'default' => '',
            'description' => t('admin.settings.mail_password_desc'),
        ]);

        OptionsRegistry::register('mail_from_address', [
            'label' => t('admin.settings.mail_from_address'),
            'type' => 'email',
            'group' => t('admin.settings.mail'),
            'default' => 'noreply@example.com',
            'description' => t('admin.settings.mail_from_address_desc'),
        ]);

        OptionsRegistry::register('mail_from_name', [
            'label' => t('admin.settings.mail_from_name'),
            'type' => 'text',
            'group' => t('admin.settings.mail'),
            'default' => config('app.name', 'Admin'),
            'description' => t('admin.settings.mail_from_name_desc'),
        ]);

        OptionsRegistry::register('mail_encryption', [
            'label' => t('admin.settings.mail_encryption'),
            'type' => 'select',
            'group' => t('admin.settings.mail'),
            'options' => [
                'tls' => 'TLS',
                'ssl' => 'SSL',
                '' => t('admin.settings.none'),
            ],
            'default' => 'tls',
            'description' => t('admin.settings.mail_encryption_desc'),
        ]);
    }
}
