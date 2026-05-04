<?php

declare(strict_types=1);

namespace Framework\Module\Mail;

use Framework\Module\BaseModule;

class MailModule extends BaseModule
{
    protected string $name = 'mail';
    protected string $path = __DIR__;
    protected ?string $serviceProvider = MailServiceProvider::class;
    protected ?string $configFile = __DIR__ . '/config.php';
    protected ?string $migrationsPath = __DIR__ . '/migrations';
    protected array $dependencies = [];
}
