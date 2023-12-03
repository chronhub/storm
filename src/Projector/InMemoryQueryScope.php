<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Projector\LoadLimiterProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\ExtractEventHeader;

final class InMemoryQueryScope implements ProjectionQueryScope
{
    public function fromIncludedPosition(): ProjectionQueryFilter
    {
        return new class() implements InMemoryQueryFilter, LoadLimiterProjectionQueryFilter
        {
            use ExtractEventHeader;

            private ?int $limit = null;

            private int $counter = 0;

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

            public function setLimit(int $limit): void
            {
                // allow override limit from option loads
                if ($this->limit !== null) {
                    return;
                }

                if ($limit < 0) {
                    throw new InvalidArgumentException('Limit must be greater than 0');
                }

                $this->limit = $limit === 0 ? PHP_INT_MAX : $limit;
            }

            public function orderBy(): string
            {
                return 'asc';
            }
        };
    }
}
