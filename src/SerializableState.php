<?php

namespace Webrtc\Mixin;

use ReflectionClass;
use ReflectionProperty;

/**
 * Export and restore every property of an object, including private parents.
 *
 * PHP's native serializer already walks the hierarchy; this helper exists so a
 * class that replaces a non-serializable property (socket, FFI handle, deferred)
 * can still round-trip the rest of its state.
 */
final class SerializableState
{
    /**
     * @param array<string, mixed> $replacements Property name => value stored instead of the live property
     * @return array<string, mixed>
     */
    public static function export(object $object, array $replacements = []): array
    {
        $state = [];
        $class = new ReflectionClass($object);
        while ($class instanceof ReflectionClass) {
            foreach ($class->getProperties() as $property) {
                if ($property->isStatic() || $property->getDeclaringClass()->getName() !== $class->getName()) {
                    continue;
                }
                $key = $class->getName() . "\0" . $property->getName();
                $name = $property->getName();
                if (array_key_exists($name, $replacements)) {
                    /** @psalm-suppress MixedAssignment */
                    $state[$key] = $replacements[$name];
                    continue;
                }
                if (!$property->isInitialized($object)) {
                    $state[$key] = ['__uninitialized' => true];
                    continue;
                }
                /** @psalm-suppress MixedAssignment */
                $state[$key] = $property->getValue($object);
            }
            $parent = $class->getParentClass();
            $class = $parent !== false ? $parent : null;
        }

        return $state;
    }

    /**
     * Snapshot the keys of a listener WeakMap into a plain list for serialization.
     *
     * A WeakMap cannot itself be serialized, so an emitter that keeps its listeners in one (weakly,
     * so a collected listener drops out on its own) exports the live keys as a list — the listener
     * objects are part of the graph and serialize by shared reference — and rebuilds the WeakMap on
     * the far side with {@see self::listToWeakMap()}.
     *
     * @template T of object
     * @param \WeakMap<T, null> $map
     * @return list<T>
     */
    public static function weakMapToList(\WeakMap $map): array
    {
        $list = [];
        foreach ($map as $key => $_) {
            $list[] = $key;
        }

        return $list;
    }

    /**
     * Rebuild a listener WeakMap (key => null) from a list produced by {@see self::weakMapToList()}.
     *
     * @template T of object
     * @param list<T> $list
     * @return \WeakMap<T, null>
     */
    public static function listToWeakMap(array $list): \WeakMap
    {
        /** @var \WeakMap<T, null> $map */
        $map = new \WeakMap();
        foreach ($list as $object) {
            $map[$object] = null;
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $state
     */
    public static function import(object $object, array $state): void
    {
        /**
         * @var mixed $value
         */
        foreach ($state as $key => $value) {
            if (!str_contains($key, "\0")) {
                continue;
            }
            $parts = explode("\0", $key, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $className = $parts[0];
            $name = $parts[1];
            if (is_array($value) && ($value['__uninitialized'] ?? false) === true) {
                continue;
            }
            if (!class_exists($className) && !trait_exists($className)) {
                continue;
            }
            /** @var class-string $className */
            $property = new ReflectionProperty($className, $name);
            $property->setValue($object, $value);
        }
    }
}
