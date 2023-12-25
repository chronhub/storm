<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Projector\Stream\Checkpoint;
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
        private readonly Chronicler $chronicler,
        private readonly NoEventStreamCounter $noEventCounter,
        callable $queryFilterResolver
    ) {
        $this->queryFilterResolver = $queryFilterResolver;
    }

    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        $hasLoadedStreams = $this->handleStreams($subscriptor);

        $this->noEventCounter->hasLoadedStreams($hasLoadedStreams);

        return $next($subscriptor);
    }

    private function handleStreams(Subscriptor $subscriptor): bool
    {
        $streams = $this->batchStreams($subscriptor->checkpoints(), $subscriptor->option()->getLoads());

        if ($streams === []) {
            return false;
        }

        $iterator = new MergeStreamIterator($subscriptor->clock(), array_keys($streams), ...array_values($streams));
        $subscriptor->setStreamIterator($iterator);

        return true;
    }

    /**
     * @param  array<string,Checkpoint>     $streams
     * @return array<string,StreamIterator>
     */
    private function batchStreams(array $streams, int $loadLimiter): array
    {
        $loadedStreams = [];

        foreach ($streams as $streamName => $stream) {
            $queryFilter = ($this->queryFilterResolver)($streamName, $stream->position + 1, $loadLimiter);

            try {
                $events = $this->chronicler->retrieveFiltered(new StreamName($streamName), $queryFilter);
                $loadedStreams[$streamName] = new StreamIterator($events);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return $loadedStreams;
    }
}
