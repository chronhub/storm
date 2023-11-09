<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface EmitterSubscriptionInterface extends PersistentSubscriptionInterface
{
    /**
     * Check if the subscription is fixed to the projector.
     */
    public function isFixed(): bool;

    /**
     * Fix the subscription to the projector.
     */
    public function fixe(): void;

    /**
     * Unfix the subscription from the projector.
     */
    public function unfix(): void;
}
