<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Contracts\Projector\TokenBucket;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Illuminate\Support\Sleep;

class BatchStreamWatcher
{
    protected int $counter;

    public function __construct(
        protected readonly ?TokenBucket $bucket,
        protected readonly ?int $fixedSleepTime = null,
    ) {
        if ($this->bucket === null && $fixedSleepTime === null) {
            throw new InvalidArgumentException('Token bucket or fixed sleep time must be provided');
        }

        $this->reset();
    }

    public function hasLoadedStreams(bool $hasLoadedStreams): void
    {
        $hasLoadedStreams ? $this->reset() : $this->counter++;
    }

    public function sleep(): void
    {
        if ($this->fixedSleepTime !== null) {
            Sleep::usleep($this->fixedSleepTime);
        } else {
            //dump('Count : '.$this->counter);

            $this->bucket->consume($this->counter);

            if ($this->counter >= $this->bucket->getCapacity()) {
                $this->reset();
            }
        }
    }

    protected function reset(): void
    {
        $this->counter = 0;
    }
}
