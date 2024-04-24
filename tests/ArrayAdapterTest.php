<?php

namespace BenTools\GuzzleHttp\Middleware\Tests;

use BenTools\GuzzleHttp\Middleware\Storage\Adapter\ArrayAdapter;
use BenTools\GuzzleHttp\Middleware\Storage\Counter;
use BenTools\GuzzleHttp\Middleware\Storage\ThrottleStorageInterface;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class ArrayAdapterTest extends TestCase
{
    public function testCreateCounter(): ThrottleStorageInterface
    {
        $storage = new ArrayAdapter();
        $this->assertFalse($storage->hasCounter('foo'));

        $counter = new Counter(10);
        $counter->increment();
        $storage->saveCounter('foo', $counter, 10);
        $this->assertTrue($storage->hasCounter('foo'));
        return $storage;
    }

    #[Depends('testCreateCounter')]
    public function testRetrieveCounter(ThrottleStorageInterface $storage): ThrottleStorageInterface
    {
        $counter = $storage->getCounter('foo');
        $this->assertInstanceOf(Counter::class, $counter);
        $this->assertEquals(1, $counter->count());
        $this->assertFalse($storage->hasCounter('bar'));
        return $storage;
    }

    #[Depends('testRetrieveCounter')]
    public function testUpdateCounter(ThrottleStorageInterface $storage): ThrottleStorageInterface
    {
        $counter = $storage->getCounter('foo');
        $this->assertNotNull($counter);
        $this->assertIsFloat($counter->getRemainingTime());
        $counter->increment();
        $this->assertEquals(2, $counter->count());
        return $storage;
    }

    #[Depends('testUpdateCounter')]
    public function testDeleteCounter(ThrottleStorageInterface $storage): void
    {
        $storage->deleteCounter('foo');
        $this->assertFalse($storage->hasCounter('foo'));
    }
}
