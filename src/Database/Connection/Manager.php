<?php

declare(strict_types=1);

namespace Framework\Database\Connection;

use Framework\Database\Contracts\ConnectionInterface;

class Manager
{
    private array $configs = [];

    private array $connections = [];

    private ?string $defaultName = null;

    public function __construct() {}

    public function connection(?string $name = null): ConnectionInterface
    {
        $name ??= $this->getDefaultName();

        if ($name === null) {
            throw new \RuntimeException('No default database connection configured.');
        }

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);
        }

        return $this->connections[$name];
    }

    public function switchTo(string $name, array $config): ConnectionInterface
    {
        $this->configs[$name] = $config;
        unset($this->connections[$name]);

        $this->connections[$name] = $this->resolve($name);
        $this->defaultName = $name;

        return $this->connections[$name];
    }

    public function switchDatabase(string $database, ?string $connection = null): ConnectionInterface
    {
        $name = $connection ?? $this->getDefaultName();

        if ($name === null) {
            throw new \RuntimeException('No default database connection configured.');
        }

        if (!isset($this->configs[$name])) {
            $config = $this->loadConfig($name);
            $this->configs[$name] = $config;
        }

        $config = $this->configs[$name];
        $config['database'] = $database;

        return $this->switchTo($name, $config);
    }

    public function purge(?string $name = null): void
    {
        if ($name === null) {
            $this->connections = [];
            return;
        }

        unset($this->connections[$name]);
    }

    public function getDefaultName(): ?string
    {
        if ($this->defaultName !== null) {
            return $this->defaultName;
        }

        $config = $this->loadConfig('default');
        if ($config !== null) {
            $this->defaultName = $config;
            return $this->defaultName;
        }

        $default = $this->loadConfig('database.default');
        if ($default !== null) {
            $this->defaultName = $default;
            return $this->defaultName;
        }

        return null;
    }

    public function setDefaultName(?string $name): void
    {
        $this->defaultName = $name;
    }

    private function resolve(string $name): ConnectionInterface
    {
        if (!isset($this->configs[$name])) {
            $config = $this->loadConfig("database.connections.{$name}");
            if ($config === null) {
                throw new \RuntimeException("Database connection [{$name}] is not configured.");
            }
            $this->configs[$name] = $config;
        }

        $factory = new ConnectionFactory();
        return $factory->make($this->configs[$name]);
    }

    private function loadConfig(string $key): mixed
    {
        if (function_exists('config')) {
            return config($key);
        }

        return null;
    }
}