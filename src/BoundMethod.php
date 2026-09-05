<?php

namespace Webrtc\Mixin;

/**
 * A serializable callable that invokes a method on an object.
 *
 * Closures cannot be serialized. `[$object, 'method']` only works for public
 * methods when invoked from Evenement. This wrapper calls through
 * {@see EventForwarder::dispatchBoundEvent()} so private handlers still run
 * after a serialize/unserialize cycle.
 */
final class BoundMethod
{
    public function __construct(
        private object $object,
        private string $method,
    ) {
    }

    public function __invoke(mixed ...$args): mixed
    {
        if ($this->object instanceof EventForwarderHost) {
            return $this->object->dispatchBoundEvent($this->method, $args);
        }

        return $this->object->{$this->method}(...$args);
    }
}
