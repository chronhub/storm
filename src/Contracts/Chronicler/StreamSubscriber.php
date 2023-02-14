<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface StreamSubscriber
{
    /**
     * Subscribe to event store
     *
     * @param  EventableChronicler  $chronicler
     * @return void
     */
    public function attachToChronicler(EventableChronicler $chronicler): void;

    /**
     * Unsubscribe from event store
     *
     * @param  EventableChronicler  $chronicler
     * @return void
     */
    public function detachFromChronicler(EventableChronicler $chronicler): void;
}
