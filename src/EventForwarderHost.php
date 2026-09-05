<?php

namespace Webrtc\Mixin;

/**
 * Object that can invoke one of its (possibly private) event handlers.
 *
 * Implemented by {@see EventForwarder}.
 */
interface EventForwarderHost
{
    /**
     * @param list<mixed> $args
     */
    public function dispatchBoundEvent(string $method, array $args): mixed;
}
