<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Cache;

use Testo\Test;
use Testo\Assert;
use Spiral\RoadRunner\KeyValue\StorageInterface;
use Spiral\RoadRunnerLaravel\Cache\RoadRunnerStore;

#[Test]
final class RoadRunnerStoreTest
{
    public function test_touch_updates_the_expiration_of_an_existing_item(): void
    {
        $storage = \Mockery::mock(StorageInterface::class)->shouldIgnoreMissing();
        $storage->shouldReceive('get')->once()->with('cache:key', \Mockery::andAnyOtherArgs())->andReturn('value');
        $storage->shouldReceive('set')->once()->with('cache:key', 'value', 60, \Mockery::andAnyOtherArgs())->andReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::true($store->touch('key', 60));
    }

    public function test_touch_returns_false_when_the_item_does_not_exist(): void
    {
        $storage = \Mockery::mock(StorageInterface::class)->shouldIgnoreMissing();
        $storage->shouldReceive('get')->once()->with('cache:key', \Mockery::andAnyOtherArgs())->andReturn(null);
        $storage->shouldReceive('set')->never();

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::false($store->touch('key', 60));
    }
}
