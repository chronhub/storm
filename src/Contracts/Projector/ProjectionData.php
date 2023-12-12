<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use JsonSerializable;

interface ProjectionData extends JsonSerializable
{
    public function toArray(): array;
}
