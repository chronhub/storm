<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Repository\InMemoryStore;
use Chronhub\Storm\Contracts\Projector\SubscriptionManagement;
use Chronhub\Storm\Projector\Repository\ReadModelSubscriptionManagement;
use Chronhub\Storm\Projector\Repository\PersistentSubscriptionManagement;

final readonly class InMemorySubscriptionFactory extends SubscriptionFactory
{
    public function createSubscriptionManagement(
        Subscription $subscription,
        string $streamName,
        ?ReadModel $readModel): SubscriptionManagement
    {
        $store = $this->createStore($subscription, $streamName);

        $store = new InMemoryStore($store);

        if ($readModel) {
            return new ReadModelSubscriptionManagement($subscription, $store, $readModel);
        }

        return new PersistentSubscriptionManagement($subscription, $store, $this->chronicler);
    }
}
