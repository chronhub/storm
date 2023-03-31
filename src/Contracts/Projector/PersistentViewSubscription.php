<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface PersistentViewSubscription extends PersistentSubscription
{
    public function isAttached(): bool;

    public function attach(): void;

    public function detach(): void;
}
