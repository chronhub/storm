<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Projector\Stream\Checkpoint;
use Chronhub\Storm\Projector\Subscription\Notification\HasBatchStreams;
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
        callable $queryFilterResolver
    ) {
        $this->queryFilterResolver = $queryFilterResolver;
    }

    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        $hasStreams = $this->handleStreams($subscriptor);

        $subscriptor->receive(new HasBatchStreams($hasStreams));

        return $next($subscriptor);
    }

    private function handleStreams(Subscriptor $subscriptor): bool
    {
        $streams = $this->batchStreams($subscriptor->checkpoints(), $subscriptor->option()->getLoads());

        if ($streams !== []) {
            $iterators = collect(array_values($streams))->map(
                fn (StreamIterator $iterator, int $key): array => [$iterator, array_keys($streams)[$key]]
            );

            $iterator = new MergeStreamIterator($subscriptor->clock(), $iterators);

            $subscriptor->setStreamIterator($iterator);

            return true;
        }

        return false;
    }

    /**
     * @param  array<string,Checkpoint>     $streams
     * @return array<string,StreamIterator>
     */
    private function batchStreams(array $streams, int $loadLimiter): array
    {
        $streamEvents = [];

        foreach ($streams as $streamName => $stream) {
            $queryFilter = ($this->queryFilterResolver)($streamName, $stream->position + 1, $loadLimiter);

            try {
                $events = $this->chronicler->retrieveFiltered(new StreamName($streamName), $queryFilter);
                $streamEvents[$streamName] = new StreamIterator($events);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return $streamEvents;
    }
}
