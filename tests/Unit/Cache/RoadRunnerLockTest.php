<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Cache;

use Testo\Test;
use Testo\Assert;
use Spiral\RoadRunner\KeyValue\StorageInterface;
use Spiral\RoadRunnerLaravel\Cache\RoadRunnerLock;

#[Test]
final class RoadRunnerLockTest
{
    public function test_acquire_stores_the_owner_with_ttl_when_free(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('has')->once()->with('lock')->andReturn(false);
        $storage->shouldReceive('set')->once()->with('lock', 'me', 15)->andReturn(true);

        $lock = new RoadRunnerLock($storage, 'lock', 15, 'me');

        Assert::true($lock->acquire());
    }

    public function test_acquire_fails_when_the_lock_is_taken(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('has')->once()->with('lock')->andReturn(true);
        $storage->shouldReceive('set')->never();

        $lock = new RoadRunnerLock($storage, 'lock', 15, 'me');

        Assert::false($lock->acquire());
    }

    public function test_release_deletes_the_lock_owned_by_the_current_process(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('get')->with('lock')->andReturn('me');
        $storage->shouldReceive('delete')->once()->with('lock')->andReturn(true);

        $lock = new RoadRunnerLock($storage, 'lock', 15, 'me');

        Assert::true($lock->release());
    }

    public function test_release_keeps_a_lock_owned_by_someone_else(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('get')->with('lock')->andReturn('other');
        $storage->shouldReceive('delete')->never();

        $lock = new RoadRunnerLock($storage, 'lock', 15, 'me');

        Assert::false($lock->release());
        Assert::false($lock->isOwnedByCurrentProcess());
    }

    public function test_force_release_deletes_regardless_of_owner(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('delete')->once()->with('lock')->andReturn(true);

        $lock = new RoadRunnerLock($storage, 'lock', 15, 'me');
        $lock->forceRelease();

        Assert::same($lock->owner(), 'me');
    }

    public function test_owner_is_generated_when_not_given(): void
    {
        $lock = new RoadRunnerLock(\Mockery::mock(StorageInterface::class), 'lock', 15);

        Assert::string($lock->owner())->matchesRegex('/^\S+$/');
    }
}
