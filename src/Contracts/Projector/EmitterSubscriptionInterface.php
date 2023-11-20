<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface EmitterSubscriptionInterface extends PersistentSubscriptionInterface
{
    /**
     * Check if the subscription is fixed to the projector.
     */
    public function isStreamFixed(): bool;

    /**
     * Fix the subscription to the projector.
     */
    public function fixeStream(): void;

    /**
     * Unfix the subscription from the projector.
     */
    public function unfixStream(): void;
}
