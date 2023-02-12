<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

interface AggregateCache
{
    /**
     * Put aggregate root in cache
     *
     * @param  AggregateRoot  $aggregateRoot
     * @return void
     */
    public function put(AggregateRoot $aggregateRoot): void;

    /**
     * Get aggregate root from cache if exists
     *
     * @param  AggregateIdentity  $aggregateId
     * @return AggregateRoot|null
     */
    public function get(AggregateIdentity $aggregateId): ?AggregateRoot;

    /**
     * Remove aggregate root from cache
     *
     * @param  AggregateIdentity  $aggregateId
     * @return void
     */
    public function forget(AggregateIdentity $aggregateId): void;

    /**
     * Flush all aggregate roots from cache
     *
     * @return void
     */
    public function flush(): void;

    /**
     * Check if aggregate root exists in cache
     *
     * @param  AggregateIdentity  $aggregateId
     * @return bool
     */
    public function has(AggregateIdentity $aggregateId): bool;
}
