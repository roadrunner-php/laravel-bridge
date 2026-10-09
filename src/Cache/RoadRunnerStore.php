<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Cache;

use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\LockProvider;
use Spiral\RoadRunner\KeyValue\StorageInterface;

final class RoadRunnerStore extends TaggableStore implements LockProvider
{
    private string $prefix;

    public function __construct(private StorageInterface $storage, string $prefix = '')
    {
        $this->setPrefix($prefix);
    }

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

        return \array_combine($keys, \iterator_to_array($this->storage->getMultiple($prefixedKeys)));
    }

    public function put($key, $value, $seconds)
    {
        return $this->storage->set($this->prefix . $key, $value, $seconds);
    }

    public function putMany(array $values, $seconds)
    {
        $prefixedValues = [];

        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix . $key] = $value;
        }

        return $this->storage->setMultiple(
            $prefixedValues,
            $seconds,
        );
    }

    public function increment($key, $value = 1)
    {
        $prefixedKey = $this->prefix . $key;
        // TTL before value: a key expiring in between is then read as missing, not as an eternal stale value.
        $expiresAt = $this->storage->getTtl($prefixedKey);
        $newValue = ((int) $this->storage->get($prefixedKey)) + $value;

        $this->storage->set(
            $prefixedKey,
            $newValue,
            // The storage expects a positive TTL in seconds; an item on the edge of expiry keeps one second.
            $expiresAt === null ? null : \max(1, $expiresAt->getTimestamp() - \time()),
        );

        return $newValue;
    }

    public function decrement($key, $value = 1)
    {
        return $this->increment($key, $value * -1);
    }

    public function forever($key, $value)
    {
        return $this->storage->set($this->prefix . $key, $value, null);
    }

    public function touch($key, $seconds): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return false;
        }

        return $this->put($key, $value, $seconds);
    }

    public function forget($key)
    {
        return $this->storage->delete($this->prefix . $key);
    }

    public function flush()
    {
        return $this->storage->clear();
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }
}
