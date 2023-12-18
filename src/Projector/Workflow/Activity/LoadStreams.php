<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Projector\Support\NoStreamLoadedCounter;
use Chronhub\Storm\Stream\StreamName;

use function array_keys;
use function array_values;

final class LoadStreams
{
    /**
     * @var callable
     */
    private $queryFilterResolver;

    public function __construct(
        private readonly NoStreamLoadedCounter $noEventCounter,
        callable $queryFilterResolver
    ) {
        $this->queryFilterResolver = $queryFilterResolver;
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $hasLoadedStreams = $this->handleStreams($subscription);

        $this->noEventCounter->hasLoadedStreams($hasLoadedStreams);

        return $next($subscription);
    }

    private function handleStreams(Subscription $subscription): bool
    {
        $streams = $this->batchStreams(
            $subscription->chronicler,
            $subscription->streamManager->all(),
            $subscription->option->getLoads()
        );

        if ($streams === []) {
            return false;
        }

        $iterator = new MergeStreamIterator($subscription->clock, array_keys($streams), ...array_values($streams));
        $subscription->setStreamIterator($iterator);

        return true;
    }

    /**
     * @return array<string,StreamIterator>
     */
    private function batchStreams(Chronicler $chronicler, array $streamPositions, int $loadLimiter): array
    {
        $streams = [];

        foreach ($streamPositions as $streamName => $streamPosition) {
            $queryFilter = ($this->queryFilterResolver)($streamName, $streamPosition + 1, $loadLimiter);

            try {
                $events = $chronicler->retrieveFiltered(new StreamName($streamName), $queryFilter);
                $streams[$streamName] = new StreamIterator($events);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return $streams;
    }
}
