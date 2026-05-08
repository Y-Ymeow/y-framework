<?php

declare(strict_types=1);

namespace Framework\Cache\Tag;

use Framework\Cache\Contracts\StoreInterface;

class TagSet
{
    private array $names;

    public function __construct(
        private StoreInterface $store,
        array|string $names,
    ) {
        $this->names = (array) $names;
    }

    public function reset(): void
    {
        foreach ($this->names as $name) {
            $this->store->delete($this->tagKey($name));
        }
    }

    public function getNamespace(): string
    {
        return implode('|', array_map(
            fn(string $name) => $this->tagVersion($name),
            $this->names,
        ));
    }

    public function tagKey(string $name): string
    {
        return 'tag:' . $name . ':key';
    }

    public function tagVersion(string $name): string
    {
        $key = $this->tagKey($name);
        $version = $this->store->get($key);

        if ($version === null) {
            $version = $this->newVersion();
            $this->store->set($key, $version);
        }

        return (string) $version;
    }

    public function getNames(): array
    {
        return $this->names;
    }

    private function newVersion(): string
    {
        return bin2hex(random_bytes(8));
    }
}
