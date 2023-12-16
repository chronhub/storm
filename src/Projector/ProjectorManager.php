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

    public function __construct(private readonly SubscriptionFactory $factory)
    {
    }

    public function newQueryProjector(array $options = []): QueryProjector
    {
        $options = $this->factory->createOption($options);

        return new ProjectQuery(
            $this->factory->createQuerySubscription($options),
            $this->factory->createContextBuilder(),
        );
    }

    public function newEmitterProjector(string $streamName, array $options = []): EmitterProjector
    {
        $options = $this->factory->createOption($options);

        return new ProjectEmitter(
            $this->factory->createEmitterSubscription($streamName, $options),
            $this->factory->createContextBuilder(),
        );
    }

    public function newReadModelProjector(string $streamName, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        $options = $this->factory->createOption($options);

        return new ProjectReadModel(
            $this->factory->createReadModelSubscription($streamName, $readModel, $options),
            $this->factory->createContextBuilder(),
        );
    }

    public function queryScope(): ?ProjectionQueryScope
    {
        return $this->factory->getQueryScope();
    }

    public function monitor(): ProjectorMonitorInterface
    {
        return $this->monitor ??= new ProjectorMonitor(
            $this->factory->getProjectionProvider(),
            $this->factory->getSerializer(),
        );
    }
}
