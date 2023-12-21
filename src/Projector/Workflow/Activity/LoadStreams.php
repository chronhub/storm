<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Projector\Stream\Checkpoint;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Projector\Support\NoEventStreamCounter;
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
        private readonly NoEventStreamCounter $noEventCounter,
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
     * @param  array<string,Checkpoint>     $streams
     * @return array<string,StreamIterator>
     */
    private function batchStreams(Chronicler $chronicler, array $streams, int $loadLimiter): array
    {
        $loadedStreams = [];

        foreach ($streams as $streamName => $stream) {
            $queryFilter = ($this->queryFilterResolver)($streamName, $stream->position + 1, $loadLimiter);

            try {
                $events = $chronicler->retrieveFiltered(new StreamName($streamName), $queryFilter);
                $loadedStreams[$streamName] = new StreamIterator($events);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return $loadedStreams;
    }
}
