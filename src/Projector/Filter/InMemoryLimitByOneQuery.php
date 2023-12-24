<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Filter;

use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Reporter\DomainEvent;

final class InMemoryLimitByOneQuery implements InMemoryQueryFilter, ProjectionQueryFilter
{
    private int $streamPosition = 0;

    public function apply(): callable
    {
        return fn (DomainEvent $event): bool => (int) $event->header(EventHeader::INTERNAL_POSITION) === $this->streamPosition;
    }

    public function setStreamPosition(int $streamPosition): void
    {
        $this->streamPosition = $streamPosition;
    }

    public function orderBy(): string
    {
        return 'asc';
    }
}
