<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

final readonly class ProjectionDetail
{
    /**
     * @param array<string,int<0,max>> $streamPositions
     */
    public function __construct(
        public array $streamPositions,
        public array $state,
    ) {
    }
}
