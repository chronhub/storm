<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Subscription\Notification;

interface Management
{
    /**
     * Stop the subscription.
     */
    public function close(): void;

    /**
     * Get the current stream name.
     */
    public function getCurrentStreamName(): string;

    public function notify(): Notification;
}
