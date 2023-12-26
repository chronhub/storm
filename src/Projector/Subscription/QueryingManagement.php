<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\QueryManagement;

final readonly class QueryingManagement implements QueryManagement
{
    public function __construct(private Notification $notification)
    {
    }

    public function close(): void
    {
        $this->notification->onProjectionStopped();
    }

    public function getCurrentStreamName(): string
    {
        return $this->notification->observeStreamName();
    }

    public function notify(): Notification
    {
        return $this->notification;
    }
}
