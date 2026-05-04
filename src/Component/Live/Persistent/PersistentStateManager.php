<?php

declare(strict_types=1);

namespace Framework\Component\Live\Persistent;

use Framework\Component\Live\Attribute\Persistent;

class PersistentStateManager
{
    private static array $drivers = [];

    private static array $driversMap = [
        'local' => LocalStorageDriver::class,
        'database' => DatabaseDriver::class,
        'cache' => CacheDriver::class,
        'redis' => RedisDriver::class,
    ];

    public static function registerDriver(string $name, string $class): void
    {
        self::$driversMap[$name] = $class;
    }

    public static function getDriver(string $driver): PersistentDriverInterface
    {
        if (!isset(self::$drivers[$driver])) {
            $class = self::$driversMap[$driver] ?? null;
            if (!$class || !class_exists($class)) {
                throw new \RuntimeException("Persistent driver [{$driver}] not found");
            }
            self::$drivers[$driver] = new $class();
        }

        return self::$drivers[$driver];
    }

    public static function syncPersistentProperty(object $component, string $propertyName): void
    {
        $ref = new \ReflectionClass($component);
        if (!$ref->hasProperty($propertyName)) {
            return;
        }

        $prop = $ref->getProperty($propertyName);
        $attrs = $prop->getAttributes(Persistent::class);

        if (empty($attrs)) {
            return;
        }

        $config = $attrs[0]->newInstance();
        $key = $config->key ?: (get_class($component) . '.' . $propertyName);
        $driver = self::getDriver($config->driver);

        $value = $prop->getValue($component);

        if ($config->encrypt) {
            $value = self::encrypt($value);
        } else {
            $value = self::serialize($value);
        }

        $driver->set($key, $value, $config->ttl);
    }

    public static function restorePersistentProperty(object $component, string $propertyName): bool
    {
        $ref = new \ReflectionClass($component);
        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        $prop = $ref->getProperty($propertyName);
        $attrs = $prop->getAttributes(Persistent::class);

        if (empty($attrs)) {
            return false;
        }

        $config = $attrs[0]->newInstance();
        $key = $config->key ?: (get_class($component) . '.' . $propertyName);
        $driver = self::getDriver($config->driver);

        if (!$driver->has($key)) {
            return false;
        }

        $value = $driver->get($key);

        if ($config->encrypt) {
            $value = self::decrypt($value);
        } else {
            $value = self::unserialize($value);
        }

        if ($value !== null) {
            $prop->setValue($component, $value);
            return true;
        }

        return false;
    }

    public static function getAllPersistentProperties(object $component): array
    {
        $properties = [];
        $ref = new \ReflectionClass($component);

        foreach ($ref->getProperties() as $prop) {
            $attrs = $prop->getAttributes(Persistent::class);
            if (!empty($attrs)) {
                $properties[] = $prop->getName();
            }
        }

        return $properties;
    }

    private static function serialize(mixed $value): string
    {
        return serialize($value);
    }

    private static function unserialize(string $value): mixed
    {
        return @unserialize($value, ['allowed_classes' => false]);
    }

    private static function encrypt(mixed $value): string
    {
        $key = config('app.key', 'secret-fallback');
        $value = self::serialize($value);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', hash('sha256', $key), 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private static function decrypt(string $value): mixed
    {
        $key = config('app.key', 'secret-fallback');
        $decoded = base64_decode($value);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', hash('sha256', $key), 0, $iv);
        return self::unserialize($decrypted);
    }
}
