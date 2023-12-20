<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Projector\Stream\Checkpoint;
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
            $subscription->streamManager->checkpoints(),
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
     * @param  array<string,Checkpoint>     $checkpoints
     * @return array<string,StreamIterator>
     */
    private function batchStreams(Chronicler $chronicler, array $checkpoints, int $loadLimiter): array
    {
        $streams = [];

        foreach ($checkpoints as $streamName => $checkpoint) {
            $queryFilter = ($this->queryFilterResolver)($streamName, $checkpoint->position + 1, $loadLimiter);

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
