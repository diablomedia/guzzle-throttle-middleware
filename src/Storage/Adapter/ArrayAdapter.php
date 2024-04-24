<?php

namespace BenTools\GuzzleHttp\Middleware\Storage\Adapter;

use BenTools\GuzzleHttp\Middleware\Storage\Counter;
use BenTools\GuzzleHttp\Middleware\Storage\ThrottleStorageInterface;

class ArrayAdapter implements ThrottleStorageInterface
{
    /**
     * @var Counter[]
     */
    private array $storage = [];

    public function hasCounter(string $storageKey): bool
    {
        return isset($this->storage[$storageKey]);
    }

    public function getCounter(string $storageKey): ?Counter
    {
        return $this->storage[$storageKey] ?? null;
    }

    public function saveCounter(string $storageKey, Counter $counter, float $ttl = null): void
    {
        $this->storage[$storageKey] = $counter;
    }

    public function deleteCounter(string $storageKey): void
    {
        unset($this->storage[$storageKey]);
    }
}
