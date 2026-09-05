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
                    $state[$key] = $replacements[$name];
                    continue;
                }
                if (!$property->isInitialized($object)) {
                    $state[$key] = ['__uninitialized' => true];
                    continue;
                }
                $state[$key] = $property->getValue($object);
            }
            $parent = $class->getParentClass();
            $class = $parent !== false ? $parent : null;
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    public static function import(object $object, array $state): void
    {
        foreach ($state as $key => $value) {
            if (!is_string($key) || !str_contains($key, "\0")) {
                continue;
            }
            [$className, $name] = explode("\0", $key, 2);
            if (is_array($value) && ($value['__uninitialized'] ?? false) === true) {
                continue;
            }
            $property = new ReflectionProperty($className, $name);
            $property->setValue($object, $value);
        }
    }
}
