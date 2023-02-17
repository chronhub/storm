<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

interface AggregateCache
{
    /**
     * Put aggregate root in cache
     */
    public function put(AggregateRoot $aggregateRoot): void;

    /**
     * Get aggregate root from cache if exists
     */
    public function get(AggregateIdentity $aggregateId): ?AggregateRoot;

    /**
     * Remove aggregate root from cache
     */
    public function forget(AggregateIdentity $aggregateId): void;

    /**
     * Flush all aggregate roots from cache
     */
    public function flush(): void;

    /**
     * Check if aggregate root exists in cache
     */
    public function has(AggregateIdentity $aggregateId): bool;
}
