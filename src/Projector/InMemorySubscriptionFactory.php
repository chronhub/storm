<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;
use Chronhub\Storm\Projector\Repository\EmitterManager;
use Chronhub\Storm\Projector\Repository\InMemoryRepository;
use Chronhub\Storm\Projector\Repository\ReadModelManager;

final class InMemorySubscriptionFactory extends AbstractSubscriptionFactory
{
    public function createSubscriptionManagement(
        EmitterSubscriptionInterface|ReadModelSubscriptionInterface $subscription,
        string $streamName,
        ?ReadModel $readModel): ProjectionManagement
    {
        $repository = $this->createRepository($subscription, $streamName);

        $adapter = new InMemoryRepository($repository);

        if ($readModel) {
            return new ReadModelManager($subscription, $adapter, $readModel);
        }

        return new EmitterManager($subscription, $adapter, $this->chronicler);
    }
}
