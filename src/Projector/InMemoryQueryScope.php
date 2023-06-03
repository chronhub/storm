<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\ExtractEventHeader;

final class InMemoryQueryScope implements ProjectionQueryScope
{
    public function fromIncludedPosition(): ProjectionQueryFilter
    {
        return new class() implements ProjectionQueryFilter, InMemoryQueryFilter
        {
            use ExtractEventHeader;

            private int $currentPosition = 0;

            public function apply(): callable
            {
                return fn (DomainEvent $event): ?DomainEvent => $this->extractInternalPosition($event) >= $this->currentPosition ? $event : null;
            }

            public function setCurrentPosition(int $streamPosition): void
            {
                $this->currentPosition = $streamPosition;
            }

            public function orderBy(): string
            {
                return 'asc';
            }
        };
    }
}
