<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\StreamNameAwareQueryFilter;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Stream\StreamName;

use function array_keys;
use function array_values;

final readonly class LoadStreams
{
    public function __construct(
        private Chronicler $chronicler,
        private SystemClock $clock
    ) {
    }

    public function batch(array $streamPositions, QueryFilter $queryFilter): MergeStreamIterator
    {
        $streams = [];

        foreach ($streamPositions as $streamName => $position) {
            if ($queryFilter instanceof ProjectionQueryFilter) {
                $queryFilter->setCurrentPosition($position + 1);
            }

            if ($queryFilter instanceof StreamNameAwareQueryFilter) {
                $queryFilter->setCurrentStreamName($streamName);
            }

            try {
                $events = $this->chronicler->retrieveFiltered(new StreamName($streamName), $queryFilter);

                $streams[$streamName] = new StreamIterator($events);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return new MergeStreamIterator($this->clock, array_keys($streams), ...array_values($streams));
    }
}
