<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use JsonSerializable;

interface PersistentState extends JsonSerializable
{
    public function put(array $state): void;

    public function get(): array;

    public function reset(): void;
}
