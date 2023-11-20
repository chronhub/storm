<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\StreamManager;

interface Subscription
{
    /**
     * Composes the subscription with a context and a new scope.
     * It also allows to keep the subscription running in background.
     */
    public function compose(ContextInterface $context, ProjectorScope $projectionScope, bool $keepRunning): void;

    /**
     * Reset user state to his original state.
     */
    public function initializeAgain(): void;

    public function &currentStreamName(): ?string;

    public function setCurrentStreamName(string $streamName): void;

    /**
     * Get the current status of the subscription.
     */
    public function currentStatus(): ProjectionStatus;

    /**
     * Set the current status of the subscription.
     */
    public function setStatus(ProjectionStatus $status): void;

    /**
     * Get the context instance.
     */
    public function context(): ContextReader;

    /**
     * Get the sprint instance.
     */
    public function sprint(): Sprint;

    /**
     * Get the state instance.
     */
    public function state(): ProjectionStateInterface;

    /**
     * Get the option instance.
     */
    public function option(): ProjectionOption;

    /**
     * Get the stream manager instance.
     */
    public function streamManager(): StreamManager;

    /**
     * Get the system clock instance.
     */
    public function clock(): SystemClock;

    /**
     * Get the chronicler instance.
     */
    public function chronicler(): Chronicler;
}
