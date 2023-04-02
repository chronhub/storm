<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface EmitterSubscriptionInterface extends PersistentSubscriptionInterface
{
    public function isJoined(): bool;

    public function join(): void;

    public function disjoin(): void;
}
