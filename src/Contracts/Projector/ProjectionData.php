<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use JsonSerializable;

/**
 * @template TKey of array-key{'status','state','position','locked_until'}
 * @template TValue of null|string
 */
interface ProjectionData extends JsonSerializable
{
    /**
     * @return array{status?: null|string, state?: null|string, position?: null|string, locked_until?: null|string}
     */
    public function toArray(): array;
}
