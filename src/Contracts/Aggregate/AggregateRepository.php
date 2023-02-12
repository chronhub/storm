<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

interface AggregateRepository
{
    /**
     * Retrieve aggregate root from aggregate id
     *
     * @param  AggregateIdentity  $aggregateId
     * @return AggregateRoot|null
     */
    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot;

    /**
     * Store aggregate root
     *
     * @param  AggregateRoot  $aggregateRoot
     * @return void
     */
    public function store(AggregateRoot $aggregateRoot): void;
}
