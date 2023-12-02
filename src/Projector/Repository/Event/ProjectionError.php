<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Event;

use Throwable;

final readonly class ProjectionError
{
    public function __construct(
        public string $streamName,
        public string $event,
        public Throwable $error
    ) {
    }
}
