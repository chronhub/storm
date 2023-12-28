<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Projector\Stream\Checkpoint;
use Chronhub\Storm\Projector\Subscription\Notification\GetCheckpoints;
use Chronhub\Storm\Projector\Subscription\Notification\StreamIteratorSet;
use Chronhub\Storm\Stream\StreamName;
use Illuminate\Support\Collection;

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
        private readonly SystemClock $clock,
        private readonly int $loadLimiter,
        callable $queryFilterResolver
    ) {
        $this->queryFilterResolver = $queryFilterResolver;
    }

    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        $checkpoints = $hub->interact(GetCheckpoints::class);

        $iterators = $this->collectStreams($this->batchStreams($checkpoints));

        if ($iterators !== null) {
            $iterators = new MergeStreamIterator($this->clock, $iterators);
        }

        $hub->interact(StreamIteratorSet::class, $iterators);

        return $next($hub);
    }

    private function collectStreams(array $streams): ?Collection
    {
        if ($streams === []) {
            return null;
        }

        return collect(array_values($streams))->map(
            fn (StreamIterator $iterator, int $key): array => [$iterator, array_keys($streams)[$key]]
        );
    }

    /**
     * @param  array<string,Checkpoint>     $streams
     * @return array<string,StreamIterator>
     */
    private function batchStreams(array $streams): array
    {
        $streamEvents = [];

        foreach ($streams as $streamName => $stream) {
            $queryFilter = ($this->queryFilterResolver)($streamName, $stream->position + 1, $this->loadLimiter);

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
