<?php

declare(strict_types=1);

namespace Framework\Serializer;

class Serializer
{
    private array $normalizers = [];

    public function __construct(array $normalizers = [])
    {
        $this->normalizers = $normalizers;
    }

    public function serialize(mixed $data, string $format = 'json'): string
    {
        return match ($format) {
            'json' => json_encode($this->normalize($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'php' => serialize($this->normalize($data)),
            default => throw new \InvalidArgumentException("Format '{$format}' is not supported."),
        };
    }

    public function deserialize(string $data, string $class, string $format = 'json'): mixed
    {
        $normalized = match ($format) {
            'json' => json_decode($data, true),
            'php' => unserialize($data),
            default => throw new \InvalidArgumentException("Format '{$format}' is not supported."),
        };

        return $this->denormalize($normalized, $class);
    }

    private function normalize(mixed $data): mixed
    {
        if ($data === null || is_scalar($data)) {
            return $data;
        }

        if (is_array($data)) {
            return array_map(fn($v) => $this->normalize($v), $data);
        }

        if ($data instanceof \DateTimeInterface) {
            return $data->format('Y-m-d H:i:s');
        }

        if ($data instanceof \JsonSerializable) {
            return $data->jsonSerialize();
        }

        if (is_object($data)) {
            $result = [];
            $reflection = new \ReflectionClass($data);

            foreach ($reflection->getProperties() as $prop) {
                if ($prop->isStatic()) {
                    continue;
                }

                $prop->setAccessible(true);
                $name = $prop->getName();
                $value = $prop->getValue($data);

                $result[$name] = $this->normalize($value);
            }

            return $result;
        }

        return $data;
    }

    private function denormalize(array $data, string $class): mixed
    {
        $reflection = new \ReflectionClass($class);
        $instance = $reflection->newInstanceWithoutConstructor();

        foreach ($data as $key => $value) {
            $prop = $reflection->getProperty($key) ?? null;

            if ($prop === null) {
                continue;
            }

            $prop->setAccessible(true);
            $prop->setValue($instance, $value);
        }

        return $instance;
    }
}
