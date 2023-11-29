<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Sprint;

trait InteractWithSubscription
{
    public function initializeAgain(): void
    {
        $this->subscription->initializeAgain();
    }

    public function &currentStreamName(): ?string
    {
        return $this->subscription->currentStreamName();
    }

    public function setCurrentStreamName(string $streamName): void
    {
        $this->subscription->setCurrentStreamName($streamName);
    }

    public function currentStatus(): ProjectionStatus
    {
        return $this->subscription->currentStatus();
    }

    public function setStatus(ProjectionStatus $status): void
    {
        $this->subscription->setStatus($status);
    }

    public function context(): ContextReaderInterface
    {
        return $this->subscription->context();
    }

    public function sprint(): Sprint
    {
        return $this->subscription->sprint();
    }

    public function state(): ProjectionStateInterface
    {
        return $this->subscription->state();
    }

    public function option(): ProjectionOption
    {
        return $this->subscription->option();
    }

    public function streamManager(): StreamManagerInterface
    {
        return $this->subscription->streamManager();
    }

    public function clock(): SystemClock
    {
        return $this->subscription->clock();
    }

    public function chronicler(): Chronicler
    {
        return $this->chronicler;
    }
}
