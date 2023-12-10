<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface QueryManagement extends Management
{
    /**
     * Stop the query subscription.
     */
    public function stop(): void;
}
