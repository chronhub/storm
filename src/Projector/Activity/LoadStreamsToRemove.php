<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\LoadLimiterProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\StreamNameAwareQueryFilter;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Stream\StreamName;

use function array_keys;
use function array_values;

/**
 * @deprecated
 */
final readonly class LoadStreamsToRemove
{
    public function __construct(
        private Chronicler $chronicler,
        private SystemClock $clock
    ) {
    }

    /**
     * @param array<non-empty-string,positive-int> $streamPositions
     * @param int<1,max>|null                      $loadLimiter
     */
    public function batch(array $streamPositions, QueryFilter $queryFilter, ?int $loadLimiter): MergeStreamIterator
    {
        $streams = [];

        foreach ($streamPositions as $streamName => $position) {
            if ($queryFilter instanceof ProjectionQueryFilter) {
                $queryFilter->setCurrentPosition($position + 1);
            }

            if ($queryFilter instanceof StreamNameAwareQueryFilter) {
                $queryFilter->setCurrentStreamName($streamName);
            }

            if ($loadLimiter && $queryFilter instanceof LoadLimiterProjectionQueryFilter) {
                $queryFilter->setLimit($loadLimiter);
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
