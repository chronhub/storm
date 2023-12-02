<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerDecorator;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Stream\StreamName;

final class EmitterSubscription implements EmitterSubscriptionInterface
{
    use InteractWithPersistentSubscription;
    use InteractWithSubscription;

    protected Chronicler $chronicler;

    protected ProjectionStateInterface $state;

    protected Sprint $sprint;

    private bool $isStreamFixed = false;

    public function __construct(
        protected ProjectionRepositoryInterface $repository,
        protected StreamManagerInterface $streamManager,
        protected ProjectionOption $option,
        protected SystemClock $clock,
        protected EventCounter $eventCounter,
        Chronicler $chronicler,
    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        $this->chronicler = $chronicler;
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
    }

    public function rise(): void
    {
        $this->mountProjection();

        $this->syncStreams();
    }

    public function store(): void
    {
        $this->repository->persist($this->persistProjectionDetail(), null);
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->sprint->stop();

        $this->resetProjection();
    }

    public function revise(): void
    {
        $this->resetProjection();

        $this->repository->reset($this->persistProjectionDetail(), $this->currentStatus());

        $this->deleteStream();
    }

    public function wasEmitted(): bool
    {
        return $this->isStreamFixed;
    }

    public function eventEmitted(): void
    {
        $this->isStreamFixed = true;
    }

    public function unsetEmitted(): void
    {
        $this->isStreamFixed = false;
    }

    private function deleteStream(): void
    {
        try {
            $this->chronicler->delete(new StreamName($this->getName()));
        } catch (StreamNotFound) {
            // fail silently
        }

        $this->unsetEmitted();
    }
}
