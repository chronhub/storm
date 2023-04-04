<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Stream\StreamName;

final readonly class EmitterManager implements ProjectionManagement
{
    use InteractWithManagement;

    public function __construct(
        private EmitterSubscriptionInterface $subscription,
        protected ProjectionRepositoryInterface $repository,
        private Chronicler $chronicler
    ) {
    }

    public function rise(): void
    {
        $this->subscription->sprint()->continue();

        if (! $this->repository->exists()) {
            $this->repository->create();
        }

        $this->repository->acquireLock();

        $this->subscription->streamPosition()->watch(
            $this->subscription->context()->queries()
        );

        $this->boundState();
    }

    public function store(): void
    {
        $this->repository->persist();
    }

    public function revise(): void
    {
        $this->repository->reset();

        $this->deleteStream();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete($withEmittedEvents);

        if ($withEmittedEvents) {
            $this->deleteStream();
        }
    }

    private function deleteStream(): void
    {
        try {
            $this->chronicler->delete(new StreamName($this->projectionName()));
        } catch (StreamNotFound) {
            //fail silently
        }

        $this->subscription->disjoin();
    }
}
