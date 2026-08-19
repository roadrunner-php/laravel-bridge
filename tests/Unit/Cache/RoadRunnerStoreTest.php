<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Spiral\RoadRunner\KeyValue\StorageInterface;
use Spiral\RoadRunnerLaravel\Cache\RoadRunnerStore;

final class RoadRunnerStoreTest extends TestCase
{
    public function test_touch_updates_the_expiration_of_an_existing_item(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())
            ->method('get')
            ->with('cache:key')
            ->willReturn('value');
        $storage->expects(self::once())
            ->method('set')
            ->with('cache:key', 'value', 60)
            ->willReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');

        self::assertTrue($store->touch('key', 60));
    }

    public function test_touch_returns_false_when_the_item_does_not_exist(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())
            ->method('get')
            ->with('cache:key')
            ->willReturn(null);
        $storage->expects(self::never())->method('set');

        $store = new RoadRunnerStore($storage, 'cache:');

        self::assertFalse($store->touch('key', 60));
    }
}
