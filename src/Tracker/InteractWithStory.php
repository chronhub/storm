<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tracker;

use Throwable;

trait InteractWithStory
{
    /**
     * Exception caught during dispatching
     */
    protected ?Throwable $exception = null;

    /**
     * Stop event propagation
     */
    protected bool $isStopped = false;

    public function __construct(protected ?string $currentEvent)
    {
    }

    public function withEvent(string $event): void
    {
        $this->currentEvent = $event;
    }

    public function currentEvent(): string
    {
        return $this->currentEvent;
    }

    public function stop(bool $stopPropagation): void
    {
        $this->isStopped = $stopPropagation;
    }

    public function isStopped(): bool
    {
        return $this->isStopped;
    }

    public function withRaisedException(Throwable $exception): void
    {
        $this->exception = $exception;
    }

    public function hasException(): bool
    {
        return $this->exception instanceof Throwable;
    }

    public function resetException(): void
    {
        $this->exception = null;
    }

    public function exception(): ?Throwable
    {
        return $this->exception;
    }
}
