<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Events;

final readonly class ProjectionDeleted
{
    public function __construct(public string $streamName)
    {
    }
}
