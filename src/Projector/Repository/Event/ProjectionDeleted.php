<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Event;

final readonly class ProjectionDeleted
{
    public function __construct(public string $streamName)
    {
    }
}
