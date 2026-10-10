<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Tests\Unit\Cache;

use Spiral\RoadRunner\KeyValue\StorageInterface;
use Spiral\RoadRunnerLaravel\Cache\RoadRunnerLock;
use Spiral\RoadRunnerLaravel\Cache\RoadRunnerStore;
use Testo\Assert;
use Testo\Test;

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

    public function test_get_reads_the_prefixed_key(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('get')->once()->with('cache:key')->andReturn('value');

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::same($store->get('key'), 'value');
    }

    public function test_put_writes_the_prefixed_key_with_ttl(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('set')->once()->with('cache:key', 'value', 30)->andReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::true($store->put('key', 'value', 30));
    }

    public function test_forever_writes_without_ttl(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('set')->once()->with('cache:key', 'value', null)->andReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::true($store->forever('key', 'value'));
    }

    public function test_many_returns_values_keyed_by_unprefixed_keys(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('getMultiple')
            ->once()
            ->with(['cache:a', 'cache:b'])
            ->andReturn(['cache:a' => 1, 'cache:b' => null]);

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::same($store->many(['a', 'b']), ['a' => 1, 'b' => null]);
    }

    public function test_put_many_prefixes_every_key(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('setMultiple')
            ->once()
            ->with(['cache:a' => 1, 'cache:b' => 2], 10)
            ->andReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::true($store->putMany(['a' => 1, 'b' => 2], 10));
    }

    public function test_increment_adds_to_the_stored_value_of_an_item_without_ttl(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('get')->with('cache:counter')->andReturn('5');
        $storage->shouldReceive('getTtl')->with('cache:counter')->andReturn(null);
        $storage->shouldReceive('set')->once()->with('cache:counter', 8, null)->andReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::same($store->increment('counter', 3), 8);
    }

    public function test_increment_starts_from_zero_for_a_missing_item(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('get')->with('cache:counter')->andReturn(null);
        $storage->shouldReceive('getTtl')->with('cache:counter')->andReturn(null);
        $storage->shouldReceive('set')->once()->with('cache:counter', 1, null)->andReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::same($store->increment('counter'), 1);
    }

    public function test_decrement_subtracts_from_the_stored_value(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('get')->with('cache:counter')->andReturn(5);
        $storage->shouldReceive('getTtl')->with('cache:counter')->andReturn(null);
        $storage->shouldReceive('set')->once()->with('cache:counter', 3, null)->andReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::same($store->decrement('counter', 2), 3);
    }

    public function test_increment_keeps_the_remaining_ttl_of_the_item(): void
    {
        $ttl = null;
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('get')->with('cache:counter')->andReturn(1);
        $storage->shouldReceive('getTtl')->with('cache:counter')->andReturn(new \DateTimeImmutable('+60 seconds'));
        $storage->shouldReceive('set')->once()->andReturnUsing(
            static function (string $key, mixed $value, null|int|\DateInterval $seconds) use (&$ttl): bool {
                $ttl = $seconds;
                return true;
            },
        );

        $store = new RoadRunnerStore($storage, 'cache:');
        $store->increment('counter');

        Assert::notNull($ttl, 'The item must not become eternal.');
        $now = new \DateTimeImmutable();
        $expiresAt = $ttl instanceof \DateInterval ? $now->add($ttl) : $now->modify("+{$ttl} seconds");
        Assert::true($expiresAt > $now->modify('+50 seconds'), 'The item must keep its remaining TTL.');
        Assert::true($expiresAt <= $now->modify('+60 seconds'), 'The item must not outlive its original TTL.');
    }

    public function test_increment_reads_the_ttl_before_the_value(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('getTtl')->once()->with('cache:counter')->andReturn(null)->ordered();
        $storage->shouldReceive('get')->once()->with('cache:counter')->andReturn(null)->ordered();
        $storage->shouldReceive('set')->once()->with('cache:counter', 1, null)->andReturn(true)->ordered();

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::same($store->increment('counter'), 1);
    }

    public function test_forget_deletes_the_prefixed_key(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('delete')->once()->with('cache:key')->andReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::true($store->forget('key'));
    }

    public function test_flush_clears_the_storage(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('clear')->once()->andReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');

        Assert::true($store->flush());
    }

    public function test_lock_is_created_on_the_prefixed_name(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('has')->once()->with('cache:job')->andReturn(false);
        $storage->shouldReceive('set')->once()->with('cache:job', 'me', 10)->andReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');
        $lock = $store->lock('job', 10, 'me');

        Assert::instanceOf($lock, RoadRunnerLock::class);
        Assert::same($lock->owner(), 'me');
        Assert::true($lock->acquire());
    }

    public function test_restore_lock_keeps_the_owner(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('get')->with('cache:job')->andReturn('me');
        $storage->shouldReceive('delete')->once()->with('cache:job')->andReturn(true);

        $store = new RoadRunnerStore($storage, 'cache:');
        $lock = $store->restoreLock('job', 'me');

        Assert::same($lock->owner(), 'me');
        Assert::true($lock->release());
    }

    public function test_prefix_can_be_changed(): void
    {
        $storage = \Mockery::mock(StorageInterface::class);
        $storage->shouldReceive('get')->once()->with('other:key')->andReturn('value');

        $store = new RoadRunnerStore($storage, 'cache:');
        $store->setPrefix('other:');

        Assert::same($store->getPrefix(), 'other:');
        Assert::same($store->get('key'), 'value');
    }
}
