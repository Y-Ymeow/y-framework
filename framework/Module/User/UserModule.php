<?php

declare(strict_types=1);

namespace Framework\Module\User;

use Framework\Module\BaseModule;

class UserModule extends BaseModule
{
    protected string $name = 'user';
    protected string $path = __DIR__;
    protected ?string $serviceProvider = UserServiceProvider::class;
    protected ?string $configFile = __DIR__ . '/config.php';
    protected ?string $migrationsPath = __DIR__ . '/migrations';
    protected array $dependencies = [];
}
