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
    private ?ProjectorMonitorInterface $monitor = null;

    public function __construct(private readonly SubscriptionFactory $subscriptionFactory)
    {
    }

    public function newQueryProjector(array $options = []): QueryProjector
    {
        $options = $this->subscriptionFactory->createOption($options);

        return new ProjectQuery(
            $this->subscriptionFactory->createQuerySubscription($options),
            $this->subscriptionFactory->createContextBuilder(),
        );
    }

    public function newEmitterProjector(string $streamName, array $options = []): EmitterProjector
    {
        $options = $this->subscriptionFactory->createOption($options);

        return new ProjectEmitter(
            $this->subscriptionFactory->createEmitterSubscription($streamName, $options),
            $this->subscriptionFactory->createContextBuilder(),
        );
    }

    public function newReadModelProjector(string $streamName, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        $options = $this->subscriptionFactory->createOption($options);

        return new ProjectReadModel(
            $this->subscriptionFactory->createReadModelSubscription($streamName, $readModel, $options),
            $this->subscriptionFactory->createContextBuilder(),
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
