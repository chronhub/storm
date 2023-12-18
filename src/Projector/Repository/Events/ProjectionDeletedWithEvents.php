<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Events;

final readonly class ProjectionDeletedWithEvents
{
    public function __construct(public string $streamName)
    {
    }
}
