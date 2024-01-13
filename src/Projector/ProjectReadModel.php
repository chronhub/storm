<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionDiscarded;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionRevised;

final readonly class ProjectReadModel implements ReadModelProjector
{
    use InteractWithProjection;

    public function __construct(
        protected ReadModelSubscriber $subscriber,
        protected ContextReader $context,
        protected string $streamName
    ) {
    }

    public function filter(ProjectionQueryFilter $queryFilter): static
    {
        $this->context->withQueryFilter($queryFilter);

        return $this;
    }

    public function run(bool $inBackground): void
    {
        $this->describeIfNeeded();

        $this->subscriber->start($this->context, $inBackground);
    }

    public function reset(): void
    {
        $this->subscriber->interact(
            fn (NotificationHub $hub) => $hub->trigger(new ProjectionRevised())
        );
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->subscriber->interact(
            fn (NotificationHub $hub) => $hub->trigger(new ProjectionDiscarded($deleteEmittedEvents))
        );
    }

    public function getName(): string
    {
        return $this->streamName;
    }
}
