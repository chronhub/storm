<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Psr\Container\ContainerInterface;

interface AggregateRepositoryManager
{
    /**
     * Create new instance of aggregate repository by stream name
     */
    public function create(string $streamName): AggregateRepository;

    /**
     * Extends the aggregate repository manager with a given stream name and callable
     *
     * @param callable{ContainerInterface, "name": string, array} $aggregateRepository
     */
    public function extends(string $streamName, callable $aggregateRepository): void;
}
