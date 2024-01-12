<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

class MasterEventCounterWatcher
{
    /**
     * @var int<0,max>
     */
    protected int $masterCount = 0;

    protected bool $doNotReset = false;

    public function increment(): void
    {
        $this->masterCount++;
    }

    public function reset(): void
    {
        if (! $this->doNotReset) {
            $this->masterCount = 0;
        }
    }

    public function current(): int
    {
        return $this->masterCount;
    }

    public function doNotReset(bool $doNotReset): void
    {
        $this->doNotReset = $doNotReset;
    }
}
