<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Reporter\DomainEvent;

interface EmitterSubscriptionInterface extends PersistentSubscriptionInterface
{
    /**
     * Create or amend to a (new) stream under the current projection name.
     */
    public function emit(DomainEvent $event): void;

    /**
     * Create or amend to a (new) stream with the given stream name.
     */
    public function linkTo(string $streamName, DomainEvent $event): void;
}
