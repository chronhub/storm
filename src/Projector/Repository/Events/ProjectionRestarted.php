<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Events;

final readonly class ProjectionRestarted
{
    public function __construct(public string $streamName)
    {
    }
}
