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
     * Get the current stream name.
     */
    public function getCurrentStreamName(): string;

    public function hub(): HookHub;
}
