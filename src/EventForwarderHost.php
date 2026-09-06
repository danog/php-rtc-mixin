<?php

namespace Webrtc\Mixin;

/**
 * Object that can invoke one of its (possibly private) event handlers.
 *
 * Implemented by {@see EventForwarder}. The one caller is {@see BoundMethod}; this exists only so
 * a serializable listener can reach a private handler, and is not public API.
 *
 * @internal
 */
interface EventForwarderHost
{
    /**
     * @internal
     * @param list<mixed> $args
     */
    public function dispatchBoundEvent(string $method, array $args): mixed;
}
