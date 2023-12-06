<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Projector\LoadLimiterProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\StreamNameAwareQueryFilter;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Stream\StreamName;

use function array_keys;
use function array_values;

final class LoadStreams
{
    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $streams = $this->getStreams($subscription);

        if ($streams !== []) {
            $iterator = new MergeStreamIterator($subscription->clock(), array_keys($streams), ...array_values($streams));

            $subscription->setStreamIterator($iterator);
        }

        return $next($subscription);
    }

    /**
     * @return array<string,StreamIterator>
     */
    private function getStreams(Subscription $subscription): array
    {
        $streams = [];

        $queryFilter = $subscription->context()->queryFilter();
        $loadLimiter = $subscription->option()->getLoads();

        foreach ($subscription->streamManager()->jsonSerialize() as $streamName => $position) {
            // todo stream name aware should only be used by query projection
            // cannot filter events for persistent projection,as we need to be consistent
            // with the stream position and gap detection
            if ($queryFilter instanceof StreamNameAwareQueryFilter) {
                $queryFilter->setCurrentStreamName($streamName);
            }

            if ($loadLimiter !== null && $queryFilter instanceof LoadLimiterProjectionQueryFilter) {
                $queryFilter->setLimit($loadLimiter);
            }

            if ($queryFilter instanceof ProjectionQueryFilter) {
                $queryFilter->setCurrentPosition($position + 1);
            }

            try {
                $events = $subscription->chronicler()->retrieveFiltered(new StreamName($streamName), $queryFilter);

                $streams[$streamName] = new StreamIterator($events);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return $streams;
    }
}
