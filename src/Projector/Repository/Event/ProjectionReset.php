<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository\Event;

use Chronhub\Storm\Projector\Repository\ProjectionDetail;

final readonly class ProjectionReset
{
    public function __construct(
        public string $streamName,
        public ProjectionDetail $projectionDetail
    ) {
    }
}
