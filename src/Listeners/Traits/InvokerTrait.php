<?php

declare(strict_types=1);

namespace Spiral\RoadRunnerLaravel\Listeners\Traits;

/**
 * @internal
 */
trait InvokerTrait
{
    /**
     * Invoke the method on an object.
     *
     * @param object $object
     * @param string $method_name
     * @param mixed  ...$args
     *
     * @return bool TRUE if the method exists and invoked, FALSE otherwise.
     */
    final protected function invokeMethod(object $object, string $method_name, ...$args): bool
    {
        if (\method_exists($object, $method_name)) {
            $object->{$method_name}(...$args);

            return true;
        }

        return false;
    }

    /**
     * Invoke the static method on class.
     *
     * @param class-string $class
     * @param string       $method_name
     * @param mixed        ...$args
     *
     * @return bool TRUE if the method exists and invoked, FALSE otherwise.
     */
    final protected function invokeStaticMethod(string $class, string $method_name, ...$args): bool
    {
        if (\method_exists($class, $method_name)) {
            $class::{$method_name}(...$args);

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
    final protected function setProperty(object $object, string $property_name, $value): bool
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

    /**
     * Change static object property value (even protected or private). Black magic is used.
     *
     * @param class-string $class
     * @param string       $property_name
     * @param mixed        $value
     *
     * @return bool TRUE if the property exists and changed, FALSE otherwise.
     */
    final protected function setStaticProperty(string $class, string $property_name, $value): bool
    {
        $changed = false;

        try {
            $instance = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

            $closure = function () use ($value, $property_name, &$changed): void {
                if (\property_exists($this, $property_name) && static::${$property_name} !== null) {
                    static::${$property_name} = $value;

                    $changed = true;
                }
            };

            $reset = $closure->bindTo($instance, $instance);
            $reset();
        } catch (\ReflectionException $e) {
            return false;
        }

        return $changed;
    }
}
