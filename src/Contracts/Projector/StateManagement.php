<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Sprint;

interface StateManagement
{
    /**
     * Composes the subscription.
     */
    public function start(bool $keepRunning): void;

    /**
     * Reset user state to his original state.
     */
    public function initializeAgain(): void;

    /**
     * Get the current stream name.
     */
    public function &currentStreamName(): ?string;

    /**
     * Set the current stream name by reference.
     */
    public function setStreamName(string &$streamName): void;

    /**
     * Get the current status of the subscription.
     */
    public function currentStatus(): ProjectionStatus;

    /**
     * Set the current status of the subscription.
     */
    public function setStatus(ProjectionStatus $status): void;

    /**
     * Set the stream iterator instance.
     */
    public function setStreamIterator(MergeStreamIterator $streamIterator): void;

    /**
     * Get the stream iterator instance if set and reset it.
     */
    public function pullStreamIterator(): ?MergeStreamIterator;

    /**
     * Get the context instance.
     */
    public function context(): ContextReaderInterface;

    /**
     * Get the sprint instance.
     */
    // public function sprint(): Sprint;

    /**
     * Get the state instance.
     */
    public function state(): ProjectionStateInterface;

    /**
     * Get the option instance.
     */
    //public function option(): ProjectionOption;

    /**
     * Get the stream manager instance.
     */
    //public function streamManager(): StreamManagerInterface;

    /**
     * Get the system clock instance.
     */
    //public function clock(): SystemClock;

    /**
     * Get the chronicler instance.
     */
    //public function chronicler(): Chronicler;
}
