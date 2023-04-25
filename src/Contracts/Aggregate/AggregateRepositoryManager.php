<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Psr\Container\ContainerInterface;

//fixMe do we need keep this interface or should be moved to integration?
interface AggregateRepositoryManager
{
    /**
     * @param non-empty-string $streamName
     */
    public function create(string $streamName): AggregateRepository;

    /**
     * @param callable(ContainerInterface, non-empty-string, array): AggregateRepository $aggregateRepository
     * @param non-empty-string                                                           $streamName
     */
    public function extends(string $streamName, callable $aggregateRepository): void;
}
