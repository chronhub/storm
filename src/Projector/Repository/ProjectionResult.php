<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

final readonly class ProjectionResult
{
    public function __construct(
        public array $checkpoints,
        public array $userState,
    ) {
    }
}
