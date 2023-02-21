<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use function is_int;

final class InMemoryProjectionQueryScope implements ProjectionQueryScope
{
    public function fromIncludedPosition(): ProjectionQueryFilter
    {
        return new class() implements ProjectionQueryFilter, InMemoryQueryFilter
        {
            private int $currentPosition = 0;

            public function apply(): callable
            {
                $position = $this->currentPosition;

                if ($position <= 0) {
                    throw new InvalidArgumentException("Position must be greater than 0, current is $position");
                }

                return static function (DomainEvent $event) use ($position): ?DomainEvent {
                    $internalPosition = $event->header(EventHeader::INTERNAL_POSITION);

                    if (! is_int($internalPosition)) {
                        throw new InvalidArgumentException("Internal position header must return an integer, current is $internalPosition");
                    }

                    return $internalPosition >= $position ? $event : null;
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
