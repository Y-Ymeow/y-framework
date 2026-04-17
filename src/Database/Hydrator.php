<?php

declare(strict_types=1);

namespace Framework\Database;

use ReflectionClass;
use ReflectionProperty;

/**
 * 极简数据灌入器
 */
final class Hydrator
{
    /**
     * 将数组灌入指定类的实例
     * @template T
     * @param class-string<T> $class
     * @return T
     */
    public function hydrate(array $data, string $class): object
    {
        $reflection = new ReflectionClass($class);
        $instance = $reflection->newInstanceWithoutConstructor();

        foreach ($data as $key => $value) {
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);
                
                // 处理基础类型转换 (可根据需要扩展)
                $value = $this->castValue($value, $property);
                
                $property->setValue($instance, $value);
            }
        }

        return $instance;
    }

    private function castValue(mixed $value, ReflectionProperty $property): mixed
    {
        if ($value === null) return null;
        
        $type = $property->getType()?->getName();
        
        return match ($type) {
            'int' => (int)$value,
            'float' => (float)$value,
            'bool' => (bool)$value,
            'DateTime', '\DateTime' => new \DateTime($value),
            default => $value,
        };
    }
}
