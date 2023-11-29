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
    public function fromIncludedPosition(int $limit = 500): ProjectionQueryFilter
    {
        return new class($limit) implements InMemoryQueryFilter, ProjectionQueryFilter
        {
            use ExtractEventHeader;

            private int $counter = 0;

            public function __construct(private readonly int $limit = 500)
            {
            }

            private int $currentPosition = 0;

            public function apply(): callable
            {
                return function (DomainEvent $event): bool {
                    if ($this->counter === $this->limit) {
                        return false;
                    }

                    $this->counter++;

                    return $this->extractInternalPosition($event) >= $this->currentPosition;
                };
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
