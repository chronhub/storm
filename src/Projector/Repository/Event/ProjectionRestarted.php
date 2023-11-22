<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Event;

final readonly class ProjectionRestarted
{
    public function __construct(public string $streamName)
    {
    }
}
