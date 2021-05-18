<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners\Traits;

/**
 * @internal
 */
trait InvokerTrait
{
    /**
     * Invoke the method on object.
     *
     * @param object $object
     * @param string $method_name
     * @param mixed  ...$args
     *
     * @return bool TRUE if the method exists and invoked, FALSE otherwise.
     */
    protected final function invokeMethod(object $object, string $method_name, ...$args): bool
    {
        if (\method_exists($object, $method_name)) {
            $object->{$method_name}(...$args);

            return true;
        }

        return false;
    }

    /**
     * Change object property value (even protected or private). Black magic is used.
     *
     * @param object $object
     * @param string $property_name
     * @param mixed  $value
     *
     * @return bool TRUE if the property exists and changed, FALSE otherwise.
     */
    protected final function setProperty(object $object, string $property_name, $value): bool
    {
        $changed = false;

        $closure = function () use ($value, $property_name, &$changed): void {
            if (\property_exists($this, $property_name) && $this->{$property_name} !== null) {
                $this->{$property_name} = $value;

                $changed = true;
            }
        };

        $reset = $closure->bindTo($object, $object);
        $reset();

        return $changed;
    }
}
