<?php

namespace BenTools\GuzzleHttp\Middleware\Storage;

interface ThrottleStorageInterface
{
    public function hasCounter(string $storageKey): bool;

    public function getCounter(string $storageKey): ?Counter;

    public function saveCounter(string $storageKey, Counter $counter, float $ttl = null): void;

    public function deleteCounter(string $storageKey): void;
}
