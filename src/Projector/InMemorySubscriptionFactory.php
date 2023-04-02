<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Repository\EmitterManager;
use Chronhub\Storm\Projector\Repository\ReadModelManager;
use Chronhub\Storm\Projector\Repository\InMemoryRepository;
use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriptionInterface;

final class InMemorySubscriptionFactory extends AbstractSubscriptionFactory
{
    public function createSubscriptionManagement(
        EmitterSubscriptionInterface|ReadModelSubscriptionInterface $subscription,
        string $streamName,
        ?ReadModel $readModel): ProjectionManagement
    {
        $store = $this->createStore($subscription, $streamName);

        $repository = new InMemoryRepository($store);

        if ($readModel) {
            return new ReadModelManager($subscription, $repository, $readModel);
        }

        return new EmitterManager($subscription, $repository, $this->chronicler);
    }
}
