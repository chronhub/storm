<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use JsonSerializable;

interface ProjectionData extends JsonSerializable
{
    /**
     * @return array{status?: ?string, state?: ?string, position?: ?string, locked_until?: ?string}
     */
    public function toArray(): array;
}
