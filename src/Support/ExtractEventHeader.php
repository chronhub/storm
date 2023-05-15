<?php

declare(strict_types=1);

namespace Chronhub\Storm\Support;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Message\Message;
use DomainException;
use function is_a;
use function is_int;

trait ExtractEventHeader
{
    /**
     * @return positive-int
     */
    protected function extractInternalPosition(Message|Reporting $event): int
    {
        $internalPosition = $event->header(EventHeader::INTERNAL_POSITION);

        if (! is_int($internalPosition) || $internalPosition < 1) {
            throw new DomainException('Internal position must be a positive integer');
        }

        return $internalPosition;
    }

    protected function extractAggregateIdentity(Message|Reporting $event): AggregateIdentity
    {
        $aggregateId = $event->header(EventHeader::AGGREGATE_ID);

        if ($aggregateId instanceof AggregateIdentity) {
            return $aggregateId;
        }

        $aggregateIdType = $event->header(EventHeader::AGGREGATE_ID_TYPE);

        if (is_a($aggregateIdType, AggregateIdentity::class, true)) {
            return $aggregateIdType::fromString($aggregateId);
        }

        throw new DomainException('Aggregate id type type must be an instance of '.AggregateIdentity::class);
    }
}
