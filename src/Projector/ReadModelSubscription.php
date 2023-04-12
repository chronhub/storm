<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;
use Chronhub\Storm\Projector\Scheme\StreamPosition;

final class ReadModelSubscription extends AbstractPersistentSubscription implements ReadModelSubscriptionInterface
{
    public function __construct(
        ProjectionRepositoryInterface $repository,
        ProjectionOption $option,
        StreamPosition $streamPosition,
        EventCounter $eventCounter,
        StreamGapDetector $gap,
        SystemClock $clock,
        private readonly ReadModel $readModel,
    ) {
       parent::__construct($repository, $option, $streamPosition, $eventCounter, $gap, $clock);
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->discoverStreams();
    }

    public function store(): void
    {
        parent::store();

        $this->readModel->persist();
    }

    public function revise(): void
    {
        parent::revise();

        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete();

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->sprint()->stop();

        $this->resetProjectionState();
    }
}
