<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRevised;

final readonly class ProjectReadModel implements ReadModelProjector
{
    use InteractWithProjection;

    public function __construct(
        protected ReadModelSubscriber $subscriber,
        protected ContextReader $context,
        private string $streamName
    ) {
    }

    public function run(bool $inBackground): void
    {
        $this->subscriber->start($this->context, $inBackground);
    }

    public function reset(): void
    {
        $this->subscriber->hub()->trigger(new ProjectionRevised());
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->subscriber->hub()->trigger(new ProjectionDiscarded($deleteEmittedEvents));
    }

    public function getName(): string
    {
        return $this->streamName;
    }
}
