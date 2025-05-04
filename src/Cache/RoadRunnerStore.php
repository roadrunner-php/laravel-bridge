<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Cache;

use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\InteractsWithTime;
use Spiral\RoadRunner\KeyValue\StorageInterface;

final class RoadRunnerStore extends TaggableStore implements LockProvider
{
    use InteractsWithTime;

    public function __construct(private StorageInterface $storage, private string $prefix = '') {}

    public function get($key)
    {
        return $this->storage->get($this->prefix . $key);
    }

    public function lock($name, $seconds = 0, $owner = null)
    {
        return new RoadRunnerLock($this->storage, $this->prefix . $name, $seconds, $owner);
    }

    public function restoreLock($name, $owner)
    {
        return $this->lock($name, 0, $owner);
    }

    public function many(array $keys)
    {
        $prefixedKeys = \array_map(fn($key) => $this->prefix . $key, $keys);

        return $this->storage->getMultiple($prefixedKeys);
    }

    public function put($key, $value, $seconds)
    {
        return $this->storage->set($this->prefix . $key, $value, $this->calculateExpiration($seconds));
    }

    public function putMany(array $values, $seconds)
    {
        $prefixedValues = [];

        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix . $key] = $value;
        }

        return $this->storage->setMultiple(
            $prefixedValues,
            $this->calculateExpiration($seconds),
        );
    }

    public function increment($key, $value = 1)
    {
        $data = $this->get($this->prefix . $key);

        return tap(((int) $data) + $value, function ($newValue) use ($key): void {
            $ttl = $this->storage->getTtl($this->prefix . $key);
            $this->storage->set($key, $newValue, $ttl->diff(new \DateTimeImmutable()));
        });
    }

    public function decrement($key, $value = 1)
    {
        return $this->increment($key, $value * -1);
    }

    public function forever($key, $value)
    {
        return $this->put($key, $value, 0);
    }

    public function forget($key)
    {
        return $this->storage->delete($this->prefix . $key);
    }

    public function flush()
    {
        return $this->storage->clear();
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set the cache key prefix.
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = !empty($prefix) ? $prefix . ':' : '';
    }

    /**
     * Get the expiration time of the key.
     */
    protected function calculateExpiration(int $seconds): int
    {
        return $this->toTimestamp($seconds);
    }

    /**
     * Get the UNIX timestamp for the given number of seconds.
     */
    protected function toTimestamp(int $seconds): int
    {
        return $seconds > 0 ? $this->availableAt($seconds) : 0;
    }
}
