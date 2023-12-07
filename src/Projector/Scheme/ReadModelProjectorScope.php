<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;

final readonly class ReadModelProjectorScope implements ReadModelProjectorScopeInterface
{
    public function __construct(private ReadModelSubscriptionInterface $subscription)
    {
    }

    public function stop(): void
    {
        $this->subscription->close();
    }

    public function readModel(): ReadModel
    {
        return $this->subscription->readModel();
    }

    public function streamName(): string
    {
        return $this->subscription->currentStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->subscription->clock();
    }
}
