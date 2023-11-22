<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

final readonly class ProjectionDetail
{
    /**
     * @param array<string,int> $streamPositions
     * @param array<int>        $streamGaps
     */
    public function __construct(
        public array $streamPositions,
        public array $state,
        public array $streamGaps,
    ) {
    }
}
