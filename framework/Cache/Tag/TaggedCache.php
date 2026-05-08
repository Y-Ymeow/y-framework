<?php

declare(strict_types=1);

namespace Framework\Cache\Tag;

use Framework\Cache\Contracts\LockInterface;
use Framework\Cache\Contracts\StoreInterface;
use Framework\Cache\Support\KeyValidator;
use Framework\Cache\Support\TtlHelper;

class TaggedCache implements StoreInterface
{
    public function __construct(
        private StoreInterface $store,
        private TagSet $tags,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        KeyValidator::validate($key);

        $taggedKey = $this->taggedKey($key);
        $value = $this->store->get($taggedKey, $default);

        return $value;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        KeyValidator::validate($key);

        $taggedKey = $this->taggedKey($key);
        $result = $this->store->set($taggedKey, $value, $ttl);

        if ($result) {
            $this->addKeyToTags($key);
        }

        return $result;
    }

    public function delete(string $key): bool
    {
        KeyValidator::validate($key);

        $taggedKey = $this->taggedKey($key);
        return $this->store->delete($taggedKey);
    }

    public function has(string $key): bool
    {
        KeyValidator::validate($key);

        $taggedKey = $this->taggedKey($key);
        return $this->store->has($taggedKey);
    }

    public function clear(): bool
    {
        return $this->flush();
    }

    public function flush(): bool
    {
        $this->tags->reset();
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        KeyValidator::validateMultiple($keys);

        $taggedKeys = [];
        foreach ($keys as $key) {
            $taggedKeys[$key] = $this->taggedKey($key);
        }

        $values = $this->store->getMultiple(array_values($taggedKeys), $default);

        $result = [];
        foreach ($taggedKeys as $originalKey => $taggedKey) {
            $result[$originalKey] = $values[$taggedKey] ?? $default;
        }

        return $result;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        KeyValidator::validateMultiple(array_keys((array) $values));

        $taggedValues = [];
        foreach ($values as $key => $value) {
            $taggedValues[$this->taggedKey((string) $key)] = $value;
        }

        $result = $this->store->setMultiple($taggedValues, $ttl);

        if ($result) {
            foreach ($values as $key => $value) {
                $this->addKeyToTags((string) $key);
            }
        }

        return $result;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        KeyValidator::validateMultiple($keys);

        $taggedKeys = [];
        foreach ($keys as $key) {
            $taggedKeys[] = $this->taggedKey($key);
        }

        return $this->store->deleteMultiple($taggedKeys);
    }

    public function increment(string $key, int $step = 1): int
    {
        KeyValidator::validate($key);

        $taggedKey = $this->taggedKey($key);
        $result = $this->store->increment($taggedKey, $step);

        if ($result === $step) {
            $this->addKeyToTags($key);
        }

        return $result;
    }

    public function decrement(string $key, int $step = 1): int
    {
        KeyValidator::validate($key);

        $taggedKey = $this->taggedKey($key);
        return $this->store->decrement($taggedKey, $step);
    }

    public function lock(string $key, int $seconds = 0): LockInterface
    {
        $taggedKey = $this->taggedKey($key);
        return $this->store->lock($taggedKey, $seconds);
    }

    public function getPrefix(): string
    {
        return $this->store->getPrefix() . $this->tags->getNamespace() . ':';
    }

    public function getTags(): TagSet
    {
        return $this->tags;
    }

    private function taggedKey(string $key): string
    {
        return $this->tags->getNamespace() . ':' . $key;
    }

    private function addKeyToTags(string $key): void
    {
        foreach ($this->tags->getNames() as $name) {
            $refKey = 'tag:' . $name . ':refs';
            $refs = $this->store->get($refKey, []);
            if (!in_array($key, $refs, true)) {
                $refs[] = $key;
                $this->store->set($refKey, $refs);
            }
        }
    }
}
