<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Contracts\Projector\ProjectorManagerInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorMonitorInterface;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\SubscriptionFactory;

final class ProjectorManager implements ProjectorManagerInterface
{
    private ProjectorMonitorInterface $monitor;

    public function __construct(private readonly SubscriptionFactory $subscriptionFactory)
    {
    }

    public function newQuery(array $options = []): QueryProjector
    {
        return new ProjectQuery(
            $this->subscriptionFactory->createQuerySubscription($options),
            $this->subscriptionFactory->createContextBuilder(),
        );
    }

    public function newEmitter(string $streamName, array $options = []): EmitterProjector
    {
        return new ProjectEmitter(
            $this->subscriptionFactory->createEmitterSubscription($streamName, $options),
            $this->subscriptionFactory->createContextBuilder(),
            $streamName
        );
    }

    public function newReadModel(string $streamName, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        return new ProjectReadModel(
            $this->subscriptionFactory->createReadModelSubscription($streamName, $readModel, $options),
            $this->subscriptionFactory->createContextBuilder(),
            $streamName,
            $readModel
        );
    }

    public function queryScope(): ?ProjectionQueryScope
    {
        return $this->subscriptionFactory->getQueryScope();
    }

    public function monitor(): ProjectorMonitorInterface
    {
        return $this->monitor ??= new ProjectorMonitor(
            $this->subscriptionFactory->getProjectionProvider(),
            $this->subscriptionFactory->getSerializer(),
        );
    }
}
