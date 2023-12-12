<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use JsonSerializable;

/**
 * @template TKey of key-of{'status', 'state', 'position', 'locked_until'}
 * @template TValue of null|string
 */
interface ProjectionData extends JsonSerializable
{
    /**
     * @return array{TKey, TValue}
     */
    public function toArray(): array;
}
