<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Projector\Subscription\Notification\GetStreamName;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private HookHub $hub)
    {
    }

    public function close(): void
    {
        $this->hub->interact(SprintStopped::class);
    }

    public function getCurrentStreamName(): string
    {
        return $this->hub->interact(GetStreamName::class);
    }

    public function hub(): HookHub
    {
        return $this->hub;
    }
}
