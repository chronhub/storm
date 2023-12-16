<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Projector\LoadLimiterProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\ExtractEventHeader;

final class InMemoryQueryScope implements ProjectionQueryScope
{
    public function fromIncludedPosition(): ProjectionQueryFilter
    {
        return new class() implements InMemoryQueryFilter, LoadLimiterProjectionQueryFilter
        {
            use ExtractEventHeader;

            private int $streamPosition;

            private int $loadLimiter;

            private int $maxPosition;

            public function apply(): callable
            {
                $this->maxPosition = $this->loadLimiter <= 0 ? PHP_INT_MAX : $this->streamPosition + $this->loadLimiter;

                return function (DomainEvent $event): bool {
                    $eventPosition = $this->extractInternalPosition($event);

                    return $eventPosition >= $this->streamPosition && $eventPosition <= $this->maxPosition;
                };
            }

            public function setLoadLimiter(int $loadLimiter): void
            {
                $this->loadLimiter = $loadLimiter;
            }

            public function setStreamPosition(int $streamPosition): void
            {
                $this->streamPosition = $streamPosition;
            }

            public function orderBy(): string
            {
                return 'asc';
            }
        };
    }
}
