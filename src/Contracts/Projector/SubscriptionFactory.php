<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Serializer\JsonSerializer;

interface SubscriptionFactory
{
    public function createQuerySubscription(array $options = []): Subscription;

    public function createEmitterSubscription(string $streamName, array $options = []): EmitterSubscriptionInterface;

    public function createReadModelSubscription(string $streamName, ReadModel $readModel, array $options = []): ReadModelSubscriptionInterface;

    public function createContextBuilder(): ContextReaderInterface;

    public function getProjectionProvider(): ProjectionProvider;

    public function getSerializer(): JsonSerializer;

    public function getQueryScope(): ?ProjectionQueryScope;
}
