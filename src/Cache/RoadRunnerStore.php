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

    #[\Override]
    public function get($key)
    {
        return $this->storage->get($this->prefix . $key);
    }

    #[\Override]
    public function lock($name, $seconds = 0, $owner = null)
    {
        return new RoadRunnerLock($this->storage, $this->prefix . $name, $seconds, $owner);
    }

    #[\Override]
    public function restoreLock($name, $owner)
    {
        return $this->lock($name, 0, $owner);
    }

    /**
     * @param array<array-key, string> $keys
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function many(array $keys)
    {
        $prefixedKeys = \array_map(fn($key) => $this->prefix . $key, $keys);

        return \array_combine($keys, \iterator_to_array($this->storage->getMultiple($prefixedKeys)));
    }

    #[\Override]
    public function put($key, $value, $seconds)
    {
        return $this->storage->set($this->prefix . $key, $value, $seconds);
    }

    /**
     * @param array<string, mixed> $values
     */
    #[\Override]
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

    #[\Override]
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

    #[\Override]
    public function decrement($key, $value = 1)
    {
        return $this->increment($key, $value * -1);
    }

    #[\Override]
    public function forever($key, $value)
    {
        return $this->storage->set($this->prefix . $key, $value, null);
    }

    #[\Override]
    public function touch($key, $seconds): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return false;
        }

        return $this->put($key, $value, $seconds);
    }

    #[\Override]
    public function forget($key)
    {
        return $this->storage->delete($this->prefix . $key);
    }

    #[\Override]
    public function flush()
    {
        return $this->storage->clear();
    }

    #[\Override]
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }
}
