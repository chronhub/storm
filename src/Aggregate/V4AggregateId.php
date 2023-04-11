<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Symfony\Component\Uid\Uuid;

final class V4AggregateId implements AggregateIdentity
{
    use HasAggregateIdentity;

    /**
     * Create new instance of aggregate identity
     */
    public static function create(): self
    {
        return new V4AggregateId(Uuid::v4());
    }
}
