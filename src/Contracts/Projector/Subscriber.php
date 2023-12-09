<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;

interface Subscriber
{
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

    public function context(): ContextInterface;

    public function outputState(): array;
}
