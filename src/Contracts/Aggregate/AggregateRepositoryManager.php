<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

interface AggregateRepositoryManager
{
    public function create(string $streamName): AggregateRepository;

    public function extends(string $streamName, callable $aggregateRepository): void;
}
