<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface SubscriptionFactory
{
    public function createQuerySubscription(array $options = []): Subscription;

    public function createEmitterSubscription(string $streamName, array $options = []): EmitterSubscriptionInterface;

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, array $options = []): ReadModelSubscriptionInterface;

    public function createContextBuilder(): ContextInterface;
}
