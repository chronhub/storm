<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\PersistentState;
use function json_encode;

final class ProjectionState implements PersistentState
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

    public function jsonSerialize(): string
    {
        return json_encode($this->state, JSON_FORCE_OBJECT, JSON_THROW_ON_ERROR);
    }
}
