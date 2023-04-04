<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Reporter\DomainEvent;
use function is_int;
use function sprintf;

final class InMemoryQueryScope implements ProjectionQueryScope
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
                     throw new InvalidArgumentException(
                         sprintf('Position must be greater than 0, current is %d', $position)
                     );
                }

                return static function (DomainEvent $event) use ($position): ?DomainEvent {
                    $internalPosition = $event->header(EventHeader::INTERNAL_POSITION);

                    if (! is_int($internalPosition)) {
                        throw new InvalidArgumentException(
                            sprintf('Internal position header must return an integer, current is %s', $internalPosition)
                        );
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
