<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface Management
{
    /**
     * Stop the subscription.
     */
    public function close(): void;

    /**
     * Get the notification hub.
     */
    public function hub(): NotificationHub;
}
