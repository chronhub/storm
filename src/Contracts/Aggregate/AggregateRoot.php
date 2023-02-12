<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Generator;
use Chronhub\Storm\Reporter\DomainEvent;

interface AggregateRoot
{
    /**
     * Reconstitute aggregate root with domain events
     *
     * @param  AggregateIdentity  $aggregateId
     * @param  Generator<DomainEvent>  $events
     * @return static|null
     */
    public static function reconstitute(AggregateIdentity $aggregateId, Generator $events): ?static;

    /**
     * Release recorded events
     *
     * @return array<DomainEvent>
     */
    public function releaseEvents(): array;

    /**
     * Return current aggregate id instance
     *
     * @return AggregateIdentity
     */
    public function aggregateId(): AggregateIdentity;

    /**
     * Return current version of aggregate root
     *
     * @return int
     */
    public function version(): int;
}
