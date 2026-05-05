<?php

declare(strict_types=1);

namespace Framework\Module\Notification;

use Framework\Module\BaseModule;

class NotificationModule extends BaseModule
{
    protected string $name = 'notification';
    protected string $path = __DIR__;
    protected ?string $serviceProvider = NotificationServiceProvider::class;
    protected ?string $configFile = __DIR__ . '/config.php';
    protected ?string $migrationsPath = __DIR__ . '/migrations';
    protected array $dependencies = ['user'];
}
