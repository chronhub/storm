<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectionStateInterface
{
    public function put(array $state): void;

    public function get(): array;

    public function reset(): void;
}
