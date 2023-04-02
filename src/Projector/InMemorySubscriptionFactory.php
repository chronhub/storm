<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Repository\EmitterRepository;
use Chronhub\Storm\Projector\Repository\ReadModelRepository;
use Chronhub\Storm\Projector\Repository\InMemoryProjectionManager;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;

final class InMemorySubscriptionFactory extends AbstractSubscriptionFactory
{
    public function createSubscriptionManagement(
        EmitterSubscriptionInterface|ReadModelSubscriptionInterface $subscription,
        string $streamName,
        ?ReadModel $readModel): ProjectionRepositoryInterface
    {
        $store = $this->createStore($subscription, $streamName);

        $adapter = new InMemoryProjectionManager($store);

        if ($readModel) {
            return new ReadModelRepository($subscription, $adapter, $readModel);
        }

        return new EmitterRepository($subscription, $adapter, $this->chronicler);
    }
}
