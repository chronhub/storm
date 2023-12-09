<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;

final readonly class ProjectReadModel implements ReadModelProjector
{
    use InteractWithProjection;

    public function __construct(protected ReadModelSubscriber $subscription)
    {
    }

    public function run(bool $inBackground): void
    {
        $this->subscription->start($inBackground);
    }

    public function getName(): string
    {
        return $this->subscription->getName();
    }
}
