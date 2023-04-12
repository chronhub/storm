<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamGapDetector;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Stream\StreamName;

final class EmitterSubscription extends AbstractPersistentSubscription implements EmitterSubscriptionInterface
{
    private bool $streamFixed = false;

    public function __construct(
        ProjectionRepositoryInterface $repository,
        ProjectionOption $option,
        StreamPosition $streamPosition,
        EventCounter $eventCounter,
        StreamGapDetector $gap,
        SystemClock $clock,
        private readonly Chronicler $chronicler
    ) {
        parent::__construct($repository, $option, $streamPosition, $eventCounter, $gap, $clock);
    }

    public function revise(): void
    {
        parent::revise();

        $this->deleteStream();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete();

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->sprint()->stop();

        $this->resetProjectionState();
    }

    public function isJoined(): bool
    {
        return $this->streamFixed;
    }

    public function join(): void
    {
        $this->streamFixed = true;
    }

    public function disjoin(): void
    {
        $this->streamFixed = false;
    }

    private function deleteStream(): void
    {
        try {
            $this->chronicler->delete(new StreamName($this->projectionName()));
        } catch (StreamNotFound) {
            //fail silently
        }

        $this->disjoin();
    }
}
