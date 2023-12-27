<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private HookHub $task)
    {
    }

    public function close(): void
    {
        $this->task->listen(SprintStopped::class);
    }

    public function getCurrentStreamName(): string
    {
        return $this->task->getStreamName();
    }

    public function hub(): HookHub
    {
        return $this->task;
    }
}
