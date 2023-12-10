<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface QuerySubscriptionManagement extends SubscriptionManagement
{
    /**
     * Stop the query subscription.
     */
    public function stop(): void;
}
