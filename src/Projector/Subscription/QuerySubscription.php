<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\QuerySubscriptionInterface;

final class QuerySubscription implements QuerySubscriptionInterface
{
    use InteractWithSubscription;

    public function __construct(protected readonly GenericSubscription $subscription)
    {
    }
}
