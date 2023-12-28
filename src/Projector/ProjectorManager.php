<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Contracts\Projector\ProjectorManagerInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorSupervisorInterface;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\SubscriptionFactory;

final class ProjectorManager implements ProjectorManagerInterface
{
    private ?ProjectorSupervisorInterface $monitor = null;

    public function __construct(private readonly SubscriptionFactory $factory)
    {
    }

    public function newQueryProjector(array $options = []): QueryProjector
    {
        $options = $this->factory->createOption($options);
        $subscription = $this->factory->createQuerySubscription($options);
        $context = $this->factory->createContextBuilder();

        return new ProjectQuery($subscription, $context);
    }

    public function newEmitterProjector(string $streamName, array $options = []): EmitterProjector
    {
        $options = $this->factory->createOption($options);
        $subscription = $this->factory->createEmitterSubscription($streamName, $options);
        $context = $this->factory->createContextBuilder();

        return new ProjectEmitter($subscription, $context, $streamName);
    }

    public function newReadModelProjector(string $streamName, ReadModel $readModel, array $options = []): ReadModelProjector
    {
        $options = $this->factory->createOption($options);
        $subscription = $this->factory->createReadModelSubscription($streamName, $readModel, $options);
        $context = $this->factory->createContextBuilder();

        return new ProjectReadModel($subscription, $context, $streamName);
    }

    public function queryScope(): ?ProjectionQueryScope
    {
        return $this->factory->getQueryScope();
    }

    public function monitor(): ProjectorSupervisorInterface
    {
        return $this->monitor ??= new ProjectorSupervisor(
            $this->factory->getProjectionProvider(),
            $this->factory->getSerializer(),
        );
    }
}
