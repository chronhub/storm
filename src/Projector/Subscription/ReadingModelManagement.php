<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelManagement;
use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class ReadingModelManagement implements ReadModelManagement
{
    use InteractWithManagement;

    public function __construct(
        protected Subscriptor $subscriptor,
        protected ProjectionRepository $repository,
        private ReadModel $readModel
    ) {
    }

    public function rise(): void
    {
        $this->mountProjection();

        if (! $this->readModel->isInitialized()) {
            $this->readModel->initialize();
        }

        $this->subscriptor->discoverStreams();

        $this->synchronise();
    }

    public function store(): void
    {
        $this->repository->persist($this->getProjectionResult());

        $this->readModel->persist();
    }

    public function revise(): void
    {
        $this->subscriptor->resetCheckpoint();
        $this->subscriptor->initializeAgain();
        $this->repository->reset($this->getProjectionResult(), $this->subscriptor->currentStatus());
        $this->readModel->reset();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->readModel->down();
        }

        $this->subscriptor->stop();
        $this->subscriptor->resetCheckpoint();
        $this->subscriptor->initializeAgain();
    }

    public function getReadModel(): ReadModel
    {
        return $this->readModel;
    }
}
