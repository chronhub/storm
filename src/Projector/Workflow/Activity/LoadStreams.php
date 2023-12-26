<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Projector\Stream\Checkpoint;
use Chronhub\Storm\Projector\Subscription\Notification;
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
        private readonly int $loadLimiter,
        private readonly SystemClock $clock,
        callable $queryFilterResolver
    ) {
        $this->queryFilterResolver = $queryFilterResolver;
    }

    public function __invoke(Notification $notification, callable $next): callable|bool
    {
        $hasStreams = $this->handleStreams($notification);

        $notification->onHasBatchStreams($hasStreams);

        return $next($notification);
    }

    private function handleStreams(Notification $notification): bool
    {
        $streams = $this->batchStreams($notification->observeCheckpoints());

        if ($streams !== []) {
            $iterators = collect(array_values($streams))->map(
                fn (StreamIterator $iterator, int $key): array => [$iterator, array_keys($streams)[$key]]
            );

            $iterator = new MergeStreamIterator($this->clock, $iterators);

            $notification->onStreamMerged($iterator);

            return true;
        }

        return false;
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
