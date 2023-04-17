<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Chronhub\Storm\Reporter\DomainEvent;
use Generator;

interface AggregateRoot
{
    /**
     * @param Generator<DomainEvent> $events
     */
    public static function reconstitute(AggregateIdentity $aggregateId, Generator $events): ?static;

    /**
     * @return array{DomainEvent}|array
     */
    public function releaseEvents(): array;

    public function aggregateId(): AggregateIdentity;

    /**
     * @return positive-int
     */
    public function version(): int;
}
