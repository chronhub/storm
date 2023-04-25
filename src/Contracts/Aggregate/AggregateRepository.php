<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

interface AggregateRepository extends AggregateQueryRepository
{
    /**
     * Store unreleased events of aggregate.
     */
    public function store(AggregateRoot $aggregateRoot): void;
}
