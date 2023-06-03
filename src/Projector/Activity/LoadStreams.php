<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\Iterator\SortStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamEventIterator;
use Chronhub\Storm\Stream\StreamName;
use function array_keys;
use function array_values;

final readonly class LoadStreams
{
    public function __construct(private Chronicler $chronicler)
    {
    }

    public function batch(array $streamPositions, QueryFilter $queryFilter): SortStreamIterator
    {
        $streams = [];

        foreach ($streamPositions as $streamName => $position) {
            if ($queryFilter instanceof ProjectionQueryFilter) {
                $queryFilter->setCurrentPosition($position + 1);
            }

            try {
                $events = $this->chronicler->retrieveFiltered(new StreamName($streamName), $queryFilter);

                $streams[$streamName] = new StreamEventIterator($events);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return new SortStreamIterator(array_keys($streams), ...array_values($streams));
    }
}
