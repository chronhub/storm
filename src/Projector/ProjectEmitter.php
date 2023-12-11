<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;

final readonly class ProjectEmitter implements EmitterProjector
{
    use InteractWithProjection;

    public function __construct(
        protected EmitterSubscriber $subscriber,
        protected ContextInterface $context
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
