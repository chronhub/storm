<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;

interface AggregateRepository
{
    public function retrieve(AggregateIdentity $aggregateId): ?AggregateRoot;

    public function retrieveFiltered(AggregateIdentity $aggregateId, QueryFilter $queryFilter): ?AggregateRoot;

    public function store(AggregateRoot $aggregateRoot): void;
}
