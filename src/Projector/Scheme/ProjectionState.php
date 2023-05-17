<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;

// todo rename state as it should reserved for state of projection with state and stream position
final class ProjectionState implements ProjectionStateInterface
{
    private array $state = [];

    public function put(array $state): void
    {
        $this->state = $state;
    }

    public function get(): array
    {
        return $this->state;
    }

    public function reset(): void
    {
        $this->state = [];
    }
}
