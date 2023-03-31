<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;

interface SubscriptionManagement
{
    public function rise(): void;

    public function close(): void;

    public function restart(): void;

    /**
     * @throws ProjectionNotFound
     */
    public function boundState(): void;

    public function disclose(): ProjectionStatus;

    public function store(): void;

    public function revise(): void;

    public function discard(bool $withEmittedEvents): void;

    public function renew(): void;

    public function freed(): void;

    public function streamName(): string;
}
