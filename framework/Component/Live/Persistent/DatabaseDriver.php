<?php

declare(strict_types=1);

namespace Framework\Component\Live\Persistent;

use Framework\Database\Model;

class ComponentStateModel extends Model
{
    protected string $table = 'component_states';

    protected array $fillable = [
        'user_id',
        'session_id',
        'component_class',
        'property_name',
        'storage_key',
        'value',
        'expires_at',
    ];

    protected array $casts = [
        'expires_at' => 'datetime',
    ];
}

class DatabaseDriver implements PersistentDriverInterface
{
    public function get(string $key): mixed
    {
        /** @var \Framework\Support\Collection|null $collection */
        $collection = ComponentStateModel::where('storage_key', $key)->first();

        if (!$collection) {
            return null;
        }

        $record = $this->collectionToModel($collection);
        if (!$record) {
            return null;
        }

        if ($record->expires_at && $record->expires_at < new \DateTime()) {
            $record->delete();
            return null;
        }

        return @unserialize($record->value, ['allowed_classes' => false]);
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $serialized = serialize($value);
        $expiresAt = $ttl ? date('Y-m-d H:i:s', time() + $ttl) : null;

        $userId = $this->getCurrentUserId();
        $sessionId = $this->getCurrentSessionId();

        $componentClass = $this->extractComponentClass($key);
        $propertyName = $this->extractPropertyName($key);

        /** @var \Framework\Support\Collection|null $collection */
        $collection = ComponentStateModel::where('storage_key', $key)->first();

        if ($collection) {
            $record = $this->collectionToModel($collection);
            if ($record) {
                $record->value = $serialized;
                $record->expires_at = $expiresAt;
                $record->save();
            }
        } else {
            ComponentStateModel::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'component_class' => $componentClass,
                'property_name' => $propertyName,
                'storage_key' => $key,
                'value' => $serialized,
                'expires_at' => $expiresAt,
            ]);
        }

        return true;
    }

    public function forget(string $key): bool
    {
        /** @var \Framework\Support\Collection|null $collection */
        $collection = ComponentStateModel::where('storage_key', $key)->first();

        if ($collection) {
            $record = $this->collectionToModel($collection);
            if ($record) {
                $record->delete();
            }
        }

        return true;
    }

    public function has(string $key): bool
    {
        /** @var \Framework\Support\Collection|null $collection */
        $collection = ComponentStateModel::where('storage_key', $key)->first();

        if (!$collection) {
            return false;
        }

        $record = $this->collectionToModel($collection);
        if (!$record) {
            return false;
        }

        if ($record->expires_at && $record->expires_at < new \DateTime()) {
            $record->delete();
            return false;
        }

        return true;
    }

    /**
     * 将 Collection 转换为 Model 实例
     */
    private function collectionToModel(\Framework\Support\Collection $collection): ?ComponentStateModel
    {
        $model = new ComponentStateModel();
        $data = $collection->toArray();

        if (empty($data)) {
            return null;
        }

        $model->fill($data);
        $model->exists = true;
        return $model;
    }

    private function getCurrentUserId(): ?string
    {
        try {
            if (function_exists('auth') && auth()->check()) {
                return (string) auth()->id();
            }
        } catch (\Throwable $e) {
            //
        }

        return null;
    }

    private function getCurrentSessionId(): ?string
    {
        try {
            if (function_exists('session')) {
                return session()->getId();
            }
        } catch (\Throwable $e) {
            //
        }

        return null;
    }

    private function extractComponentClass(string $key): string
    {
        $parts = explode('.', $key);
        return $parts[0] ?? '';
    }

    private function extractPropertyName(string $key): string
    {
        $parts = explode('.', $key);
        return $parts[1] ?? '';
    }
}
