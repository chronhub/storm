<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support;

use Chronhub\Storm\Contracts\Projector\TokenBucket;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

use function usleep;

final class BatchStreamsAware
{
    private int $counter;

    public function __construct(
        private readonly ?TokenBucket $bucket,
        private readonly ?int $fixedSleepTime = null,
    ) {
        if ($this->bucket === null && $fixedSleepTime === null) {
            throw new InvalidArgumentException('Token bucket or fixed sleep time must be provided');
        }

        $this->reset();

        // Overflow the bucket to sleep for every increment
        // issue: on the first increment which does not sleep at all
        // because of the exact zero token
        $this->bucket?->consume($this->bucket->getCapacity());
    }

    public function hasLoadedStreams(bool $hasLoadedStreams): void
    {
        $hasLoadedStreams ? $this->reset() : $this->counter++;
    }

    public function reset(): void
    {
        $this->counter = 0;
    }

    public function sleep(): void
    {
        if ($this->fixedSleepTime !== null) {
            usleep($this->fixedSleepTime);

            return;
        }

        // dump('Consume tokens: '.$this->counter);

        $this->consumeTokens();

        if ($this->counter >= $this->bucket->getCapacity()) {
            $this->reset();
        }
    }

    private function consumeTokens(): void
    {
        $this->bucket?->consume($this->counter);
    }
}
