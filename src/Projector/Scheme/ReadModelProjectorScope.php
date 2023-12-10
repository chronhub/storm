<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;

final readonly class ReadModelProjectorScope implements ReadModelProjectorScopeInterface
{
    public function __construct(
        private SubscriptionManagement $management,
        private ReadModelSubscriber $subscriber
    ) {
    }

    public function stop(): void
    {
        $this->management->close();
    }

    public function readModel(): ReadModel
    {
        return $this->subscriber->readModel();
    }

    public function streamName(): string
    {
        return $this->subscriber->subscription->currentStreamName();
    }

    public function clock(): SystemClock
    {
        return $this->subscriber->subscription->clock;
    }
}
