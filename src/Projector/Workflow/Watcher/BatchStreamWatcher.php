<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Contracts\Projector\TokenBucket;

class BatchStreamWatcher
{
    protected int $counter;

    public function __construct(protected readonly TokenBucket $bucket)
    {
        $this->reset();
    }

    public function hasLoadedStreams(bool $hasLoadedStreams): void
    {
        $hasLoadedStreams ? $this->reset() : $this->counter++;
    }

    public function sleep(): void
    {
        //dump('Count : '.$this->counter);

        $this->bucket->consume($this->counter);

        if ($this->counter >= $this->bucket->getCapacity()) {
            $this->reset();
        }
    }

    protected function reset(): void
    {
        $this->counter = 0;
    }
}
