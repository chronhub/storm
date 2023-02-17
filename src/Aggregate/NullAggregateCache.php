<?php

declare(strict_types=1);

namespace Chronhub\Storm\Aggregate;

use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateCache;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

final class NullAggregateCache implements AggregateCache
{
    public function put(AggregateRoot $aggregateRoot): void
    {
    }

    public function get(AggregateIdentity $aggregateId): ?AggregateRoot
    {
        return null;
    }

    public function forget(AggregateIdentity $aggregateId): void
    {
    }

    public function flush(): void
    {
       //
    }

    public function has(AggregateIdentity $aggregateId): bool
    {
        return false;
    }

    public function count(): int
    {
        return 0;
    }
}
