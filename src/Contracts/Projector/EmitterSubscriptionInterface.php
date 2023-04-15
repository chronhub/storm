<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface EmitterSubscriptionInterface extends PersistentSubscriptionInterface
{
    /**
     * Check if the subscription is joined to the projector.
     */
    public function isJoined(): bool;

    /**
     * Join the subscription to the projector.
     */
    public function join(): void;

    /**
     * Disjoin the subscription from the projector.
     */
    public function disjoin(): void;
}
