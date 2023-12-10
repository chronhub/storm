<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Projector\Repository\InMemoryRepository;

final class InMemorySubscriptionFactory extends AbstractSubscriptionFactory
{
    private bool $useEvents = false;

    public function useEvents(bool $useEvents): void
    {
        $this->useEvents = $useEvents;
    }

    protected function createProjectionRepository(string $streamName, ProjectionOption $options): ProjectionRepository
    {
        $repository = new InMemoryRepository(
            $this->projectionProvider,
            $this->createLockManager($options),
            $this->jsonSerializer,
            $streamName
        );

        if (! $this->useEvents) {
            return $repository;
        }

        return $this->createDispatcherRepository($repository);
    }
}
