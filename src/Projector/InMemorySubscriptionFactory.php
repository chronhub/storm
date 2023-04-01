<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Projector\Repository\InMemoryProjectionStore;
use Chronhub\Storm\Contracts\Projector\PersistentViewSubscription;
use Chronhub\Storm\Projector\Repository\ReadModelProjectionRepository;
use Chronhub\Storm\Contracts\Projector\PersistentReadModelSubscription;
use Chronhub\Storm\Projector\Repository\PersistentProjectionRepository;

final class InMemorySubscriptionFactory extends AbstractSubscriptionFactory
{
    public function createSubscriptionManagement(
        PersistentViewSubscription|PersistentReadModelSubscription $subscription,
        string $streamName,
        ?ReadModel $readModel): ProjectionRepository
    {
        $store = $this->createStore($subscription, $streamName);

        $adapter = new InMemoryProjectionStore($store);

        if ($readModel) {
            return new ReadModelProjectionRepository($subscription, $adapter, $readModel);
        }

        return new PersistentProjectionRepository($subscription, $adapter, $this->chronicler);
    }
}
