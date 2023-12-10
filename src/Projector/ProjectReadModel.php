<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;

final readonly class ProjectReadModel implements ReadModelProjector
{
    use InteractWithProjection;

    public function __construct(protected ReadModelSubscriber $subscriber)
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
