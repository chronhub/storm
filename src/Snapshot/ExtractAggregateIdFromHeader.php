<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Reporter\DomainEvent;

trait ExtractAggregateIdFromHeader
{
    protected function extractAggregateId(DomainEvent $event): AggregateIdentity
    {
        $aggregateId = $event->header(EventHeader::AGGREGATE_ID);

        if ($aggregateId instanceof AggregateIdentity) {
            return $aggregateId;
        }

        /** @var AggregateIdentity $aggregateIdType */
        $aggregateIdType = $event->header(EventHeader::AGGREGATE_ID_TYPE);

        return $aggregateIdType::fromString($aggregateId);
    }
}
