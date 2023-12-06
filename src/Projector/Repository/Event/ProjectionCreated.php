<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Event;

final class ProjectionCreated
{
    public function __construct(public string $streamName)
    {
    }
}
