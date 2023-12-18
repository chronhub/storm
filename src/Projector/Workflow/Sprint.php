<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

class Sprint
{
    protected bool $runInBackground = false;

    protected bool $inProgress = false;

    public function continue(): void
    {
        $this->inProgress = true;
    }

    public function stop(): void
    {
        $this->inProgress = false;
    }

    public function inProgress(): bool
    {
        return $this->inProgress;
    }

    public function runInBackground(bool $runInBackground): void
    {
        $this->runInBackground = $runInBackground;
    }

    public function inBackground(): bool
    {
        return $this->runInBackground;
    }
}
