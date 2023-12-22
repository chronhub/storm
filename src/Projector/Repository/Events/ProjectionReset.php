<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Events;

use Chronhub\Storm\Projector\Repository\ProjectionResult;

final readonly class ProjectionReset
{
    public function __construct(
        public string $streamName,
        public ProjectionResult $projectionDetail
    ) {
    }
}
