<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

interface AggregateRepository
{
    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot;

    public function store(AggregateRoot $aggregateRoot): void;
}
