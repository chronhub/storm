<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionDiscarded;
use Chronhub\Storm\Projector\Support\Notification\Management\ProjectionRevised;

final readonly class ProjectEmitter implements EmitterProjector
{
    use InteractWithProjection;

    public function __construct(
        protected EmitterSubscriber $subscriber,
        protected ContextReader $context,
        protected string $streamName
    ) {
    }

    public function run(bool $inBackground): void
    {
        $this->describeIfNeeded();

        $this->subscriber->start($this->context, $inBackground);
    }

    public function reset(): void
    {
        $this->subscriber->interact(
            fn(NotificationHub $hub) => $hub->trigger(new ProjectionRevised())
        );
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->subscriber->interact(
            fn(NotificationHub $hub) => $hub->trigger(new ProjectionDiscarded($deleteEmittedEvents))
        );
    }

    public function getName(): string
    {
        return $this->streamName;
    }
}
