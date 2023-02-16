<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Projector\Store;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;

final readonly class PersistentProjectorRepository implements ProjectorRepository
{
    public function __construct(private Context $context,
                                private Store $store,
                                private Chronicler $chronicler)
    {
    }

    public function rise(): void
    {
        $this->context->runner->stop(false);

        if (! $this->store->exists()) {
            $this->store->create();
        }

        $this->store->acquireLock();

        $this->context->streamPosition->watch($this->context->queries());

        $this->boundState();
    }

    public function store(): void
    {
        $this->store->persist();
    }

    public function revise(): void
    {
        $this->store->reset();

        $this->deleteStream();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->store->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }
    }

    public function renew(): void
    {
        $this->store->updateLock();
    }

    public function freed(): void
    {
        $this->store->releaseLock();
    }

    public function boundState(): void
    {
        $this->store->loadState();
    }

    public function close(): void
    {
        $this->store->stop();
    }

    public function restart(): void
    {
        $this->store->startAgain();
    }

    public function disclose(): ProjectionStatus
    {
        return $this->store->loadStatus();
    }

    public function streamName(): string
    {
        return $this->store->currentStreamName();
    }

    private function deleteStream(): void
    {
        try {
            $this->chronicler->delete(new StreamName($this->streamName()));
        } catch (StreamNotFound) {
        }

        $this->context->isStreamCreated = false;
    }
}
