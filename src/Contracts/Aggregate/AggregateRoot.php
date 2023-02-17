<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Aggregate;

use Generator;
use Chronhub\Storm\Reporter\DomainEvent;

interface AggregateRoot
{
    /**
     * @param  Generator{DomainEvent}  $events
     */
    public static function reconstitute(AggregateIdentity $aggregateId, Generator $events): ?static;

    /**
     * @return array{DomainEvent}
     */
    public function releaseEvents(): array;

    public function aggregateId(): AggregateIdentity;

    public function version(): int;
}
