<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriber;

final readonly class ProjectEmitter implements EmitterProjector
{
    use InteractWithProjection;

    public function __construct(protected EmitterSubscriber $subscriber)
    {
    }

    public function run(bool $inBackground): void
    {
        $this->subscriber->start($inBackground);
    }

    public function getName(): string
    {
        return $this->subscriber->getName();
    }
}
