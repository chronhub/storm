<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

class Runner
{
    protected bool $runInBackground = false;

    protected bool $isStopped = false;

    public function runInBackground(bool $runInBackground): void
    {
        $this->runInBackground = $runInBackground;
    }

    public function inBackground(): bool
    {
        return $this->runInBackground;
    }

    public function isStopped(): bool
    {
        return $this->isStopped;
    }

    public function stop(bool $isStopped): void
    {
        $this->isStopped = $isStopped;
    }
}
