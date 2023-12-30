<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

final class MasterEventCounterWatcher
{
    /**
     * @var int<0,max>
     */
    protected int $masterCount = 0;

    public function increment(): void
    {
        $this->masterCount++;
    }

    public function reset(): void
    {
        $this->masterCount = 0;
    }

    public function current(): int
    {
        return $this->masterCount;
    }
}
