<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;

final readonly class ProjectReadModel implements ReadModelProjector
{
    use InteractWithProjection;

    public function __construct(
        protected ReadModelSubscriber $subscriber,
        protected ContextReader $context
    ) {
    }

    public function run(bool $inBackground): void
    {
        $this->subscriber->start($this->context, $inBackground);
    }

    public function reset(): void
    {
        $this->subscriber->reset();
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->subscriber->delete($deleteEmittedEvents);
    }

    public function getName(): string
    {
        return $this->subscriber->getName();
    }
}
