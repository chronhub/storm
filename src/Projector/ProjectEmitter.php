<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRevised;

final readonly class ProjectEmitter implements EmitterProjector
{
    use InteractWithProjection;

    public function __construct(
        protected EmitterSubscriber $subscriber,
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
        $this->subscriber->notify()->dispatch(new ProjectionRevised());
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->subscriber->notify()->dispatch(new ProjectionDiscarded($deleteEmittedEvents));
    }

    public function getName(): string
    {
        return $this->streamName;
    }
}
