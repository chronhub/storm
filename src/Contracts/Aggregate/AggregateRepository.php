<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

interface AggregateRepository
{
    /**
     * Retrieve aggregate root from aggregate id
     */
    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot;

    /**
     * Store aggregate root
     */
    public function store(AggregateRoot $aggregateRoot): void;
}
