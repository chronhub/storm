<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface EmitterSubscriptionInterface extends PersistentSubscriptionInterface
{
    /**
     * Check if an event has been emitted.
     */
    public function wasEmitted(): bool;

    /**
     * Set the emitted event to true.
     */
    public function eventEmitted(): void;

    /**
     * Unset the emitted event to false.
     *
     * Only happens when the stream is deleted.
     * checkMe: probably not required as deleting the stream will put down the projection
     */
    public function unsetEmitted(): void;
}
