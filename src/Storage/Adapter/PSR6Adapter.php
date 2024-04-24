<?php

namespace BenTools\GuzzleHttp\Middleware\Storage\Adapter;

use BenTools\GuzzleHttp\Middleware\Storage\Counter;
use BenTools\GuzzleHttp\Middleware\Storage\ThrottleStorageInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class PSR6Adapter
 * Needs PSR-6 psr/cache implementation (like symfony/cache)
 */
class PSR6Adapter implements ThrottleStorageInterface
{
    private CacheItemPoolInterface $cacheItemPool;

    /**
     * PSR6Adapter constructor.
     */
    public function __construct(CacheItemPoolInterface $cacheItemPool)
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    public function hasCounter(string $storageKey): bool
    {
        return $this->cacheItemPool->hasItem($storageKey);
    }

    public function getCounter(string $storageKey): ?Counter
    {
        $item = $this->cacheItemPool->getItem($storageKey);
        if ($item->isHit()) {
            $counter = $item->get();
            if (is_string($counter)) {
                $counter = unserialize($counter);
                if (!$counter instanceof Counter) {
                    $counter = null;
                }
            } else {
                $counter = null;
            }
        } else {
            $counter = null;
        }

        return $counter;
    }

    public function saveCounter(string $storageKey, Counter $counter, float $ttl = null): void
    {
        $item = $this->cacheItemPool->getItem($storageKey);
        $item->set(serialize($counter));
        if (null !== $ttl) {
            $item->expiresAfter((int) ceil($ttl));
        }
        $this->cacheItemPool->save($item);
    }

    public function deleteCounter(string $storageKey): void
    {
        $this->cacheItemPool->deleteItem($storageKey);
    }
}
