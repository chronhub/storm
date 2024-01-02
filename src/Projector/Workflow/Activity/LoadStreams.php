<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Projector\Stream\Checkpoint;
use Chronhub\Storm\Projector\Subscription\Checkpoint\CurrentCheckpoint;
use Chronhub\Storm\Projector\Subscription\Stream\StreamIteratorSet;
use Chronhub\Storm\Stream\StreamName;
use Illuminate\Support\Collection;

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

    public function __invoke(NotificationHub $hub, callable $next): callable|bool
    {
        $checkpoints = $hub->expect(CurrentCheckpoint::class);

        $streams = $this->collectStreams($checkpoints);

        if ($streams) {
            $streams = new MergeStreamIterator($this->clock, $streams);
        }

        $hub->notify(StreamIteratorSet::class, $streams);

        return $next($hub);
    }

    /**
     * @param  array<string,Checkpoint>               $streams
     * @return null|Collection<string,StreamIterator>
     */
    private function collectStreams(array $streams): ?Collection
    {
        $streamEvents = new Collection();

        foreach ($streams as $streamName => $stream) {
            $queryFilter = ($this->queryFilterResolver)($streamName, $stream->position + 1, $this->loadLimiter);

            try {
                $events = $this->chronicler->retrieveFiltered(new StreamName($streamName), $queryFilter);
                $streamEvents->push([new StreamIterator($events), $streamName]);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return $streamEvents->isEmpty() ? null : $streamEvents;
    }
}
