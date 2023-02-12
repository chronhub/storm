<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Symfony\Component\Uid\Uuid;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

final class V4AggregateId implements AggregateIdentity
{
    use HasAggregateIdentity;

    /**
     * Create new instance of aggregate id
     *
     * @return static|AggregateIdentity
     */
    public static function create(): V4AggregateId|AggregateIdentity
    {
        return new V4AggregateId(Uuid::v4());
    }
}
