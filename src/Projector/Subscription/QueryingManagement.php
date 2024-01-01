<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Projector\Subscription\Sprint\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Stream\CurrentProcessedStream;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private NotificationHub $hub)
    {
    }

    public function close(): void
    {
        $this->hub->notify(SprintStopped::class);
    }

    public function getProcessedStream(): string
    {
        return $this->hub->expect(CurrentProcessedStream::class);
    }

    public function hub(): NotificationHub
    {
        return $this->hub;
    }
}
