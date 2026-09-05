<?php

namespace Webrtc\Mixin;

use Evenement\EventEmitterInterface;

/**
 * A serializable listener that re-emits an event on another emitter.
 *
 * Replaces `function () use ($event) { $this->emit($event, func_get_args()); }`.
 */
final class EventReemitter
{
    public function __construct(
        private EventEmitterInterface $target,
        private string $event,
    ) {
    }

    public function __invoke(mixed ...$args): void
    {
        $this->target->emit($this->event, $args);
    }
}
