<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\Repository\InMemoryRepository;

final class InMemorySubscriptionFactory extends AbstractSubscriptionFactory
{
    protected function createSubscriptionManagement(string $streamName, ProjectionOption $options): ProjectionRepositoryInterface
    {
        $repository = $this->createRepository($streamName, $options);

        return new InMemoryRepository($repository);
    }
}
