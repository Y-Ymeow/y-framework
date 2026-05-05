<?php

declare(strict_types=1);

namespace Admin\Content;

trait HasMeta
{
    public function getMeta(string $key, mixed $default = null): mixed
    {
        $result = db()->table('meta')
            ->where('metable_type', static::class)
            ->where('metable_id', $this->id)
            ->where('key', $key)
            ->first();

        if (!$result) {
            return $default;
        }

        $value = $result['value'] ?? null;

        if ($value !== null) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return $value;
    }

    public function setMeta(string $key, mixed $value): void
    {
        $storedValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;

        $exists = db()->table('meta')
            ->where('metable_type', static::class)
            ->where('metable_id', $this->id)
            ->where('key', $key)
            ->exists();

        if ($exists) {
            db()->table('meta')
                ->where('metable_type', static::class)
                ->where('metable_id', $this->id)
                ->where('key', $key)
                ->update(['value' => $storedValue]);
        } else {
            db()->table('meta')->insert([
                'metable_type' => static::class,
                'metable_id' => $this->id,
                'key' => $key,
                'value' => $storedValue,
            ]);
        }
    }

    public function deleteMeta(string $key): void
    {
        db()->table('meta')
            ->where('metable_type', static::class)
            ->where('metable_id', $this->id)
            ->where('key', $key)
            ->delete();
    }

    public function getAllMeta(): array
    {
        $results = db()->table('meta')
            ->where('metable_type', static::class)
            ->where('metable_id', $this->id)
            ->get()
            ->toArray();

        $meta = [];
        foreach ($results as $row) {
            $value = $row['value'] ?? null;
            if ($value !== null) {
                $decoded = json_decode($value, true);
                $meta[$row['key']] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $value;
            } else {
                $meta[$row['key']] = null;
            }
        }

        return $meta;
    }

    public function syncMeta(array $metaData): void
    {
        foreach ($metaData as $key => $value) {
            $this->setMeta($key, $value);
        }
    }

    public function purgeMeta(): void
    {
        db()->table('meta')
            ->where('metable_type', static::class)
            ->where('metable_id', $this->id)
            ->delete();
    }
}
