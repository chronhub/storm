<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Illuminate\Support\Collection;

use function array_fill_keys;
use function array_values;

class GapsCollection
{
    /**
     * @var Collection<array<non-empty-string,array<int<1,max>,bool>>>
     */
    protected Collection $gaps;

    public function __construct()
    {
        $this->gaps = new Collection();
    }

    /**
     * Adds a new event position and its confirmation status to the specified stream.
     *
     * @param non-empty-string $streamName    The name of the stream.
     * @param int<1,max>       $eventPosition The position of the event.
     * @param bool             $confirmed     The confirmation status of gap.
     */
    public function put(string $streamName, int $eventPosition, bool $confirmed): void
    {
        $streamGap = $this->gaps->get($streamName, new Collection());

        $this->gaps->put($streamName, $streamGap->put($eventPosition, $confirmed));
    }

    /**
     * Removes the specified event position from the specified stream.
     *
     * @param non-empty-string $streamName
     * @param int<1,max>       $eventPosition
     */
    public function remove(string $streamName, int $eventPosition): void
    {
        $streamGap = $this->gaps->get($streamName, new Collection());

        $isGapConfirmed = $streamGap->get($eventPosition);

        if ($isGapConfirmed === null) {
            return;
        }

        if ($isGapConfirmed === true) {
            throw new InvalidArgumentException("Cannot remove confirmed event position $eventPosition for stream $streamName");
        }

        $this->gaps->put($streamName, $streamGap->forget($eventPosition));
    }

    /**
     * Merge remote stream gaps with local gaps
     *
     * @param array<int> $streamGaps
     */
    public function merge(string $streamName, array $streamGaps): void
    {
        if ($streamGaps === []) {
            return;
        }

        $streamGap = $this->gaps->get($streamName, new Collection());

        $confirmedGaps = array_fill_keys(array_values($streamGaps), true);

        $this->gaps->put($streamName, $streamGap->merge($confirmedGaps));
    }

    /**
     * Filter confirmed gaps and remove unconfirmed local gaps
     * when projection is persisted
     *
     * @return array<int<1,max>>
     */
    public function filterConfirmedGaps(string $streamName): array
    {
        $streamGap = $this->gaps->get($streamName, new Collection());

        $confirmedGaps = $streamGap->filter(fn (bool $confirmed) => $confirmed);

        $this->gaps->put($streamName, $confirmedGaps);

        return $confirmedGaps->keys()->all();
    }

    /**
     * Return a clone of gaps collection
     */
    public function all(): Collection
    {
        return clone $this->gaps;
    }
}
