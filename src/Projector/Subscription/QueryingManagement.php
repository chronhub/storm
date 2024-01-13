<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Projector\Support\Notification\Sprint\SprintStopped;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private NotificationHub $hub)
    {
    }

    public function close(): void
    {
        $this->hub->notify(SprintStopped::class);
    }

    public function hub(): NotificationHub
    {
        return $this->hub;
    }
}
