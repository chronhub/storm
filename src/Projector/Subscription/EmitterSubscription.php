<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamGapManager;
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
        StreamGapManager $gap,
        SystemClock $clock,
        private readonly Chronicler $chronicler
    ) {
        parent::__construct($option, $streamPosition, $clock);

        $this->repository = $repository;
        $this->eventCounter = $eventCounter;
        $this->gap = $gap;
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

        $this->resetProjection();
    }

    public function isFixed(): bool
    {
        return $this->streamFixed;
    }

    public function fixe(): void
    {
        $this->streamFixed = true;
    }

    public function unfix(): void
    {
        $this->streamFixed = false;
    }

    private function deleteStream(): void
    {
        try {
            $this->chronicler->delete(new StreamName($this->projectionName()));
        } catch (StreamNotFound) {
            // fail silently
        }

        $this->unfix();
    }
}
