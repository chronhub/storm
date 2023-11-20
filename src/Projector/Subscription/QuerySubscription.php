<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

final readonly class QuerySubscription implements Subscription
{
    use InteractWithSubscription {
        compose as protected compoQuery;
    }

    public function __construct(protected GenericSubscription $subscription)
    {
    }

    protected function composeQuery(ContextInterface $context, ProjectorScope $projectionScope, bool $keepRunning): void
    {
        if ($context->queryFilter() instanceof ProjectionQueryFilter) {
            throw new InvalidArgumentException('Projection Query filter is not supported for query subscription');
        }

        $this->compose($context, $projectionScope, $keepRunning);
    }
}
