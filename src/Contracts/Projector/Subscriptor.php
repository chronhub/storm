<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Workflow\Watcher\WatcherManager;

interface Subscriptor
{
    /**
     * Discover streams to project.
     */
    public function discoverStreams(): void;

    /**
     * Set the context of the projection.
     */
    public function setContext(ContextReader $context, bool $allowRerun): void;

    /**
     * Get the context of the projection.
     */
    public function getContext(): ?ContextReader;

    /**
     * Restore user state.
     */
    public function restoreUserState(): void;

    /**
     * Check if user state is initialized.
     */
    public function isUserStateInitialized(): bool;

    /**
     * Set projection status.
     */
    public function setStatus(ProjectionStatus $status): void;

    /**
     * Get projection status.
     */
    public function currentStatus(): ProjectionStatus;

    /**
     * Get the processed stream name.
     */
    public function getProcessedStream(): string;

    /**
     * Set the processed stream name.
     */
    public function setProcessedStream(string $streamName): void;

    /**
     * Set the stream iterator.
     */
    public function setStreamIterator(?MergeStreamIterator $streamIterator): void;

    /**
     * Pull the stream iterator.
     */
    public function pullStreamIterator(): ?MergeStreamIterator;

    /**
     * Get the checkpoint manager.
     */
    public function recognition(): CheckpointRecognition;

    /**
     * Get the projection watcher.
     */
    public function watcher(): WatcherManager;

    /**
     * Capture event and return the result if it can apply.
     */
    public function capture(callable|object $event): mixed;

    /**
     * Get the projection option.
     */
    public function option(): ProjectionOption;

    /**
     * Get the clock instance.
     */
    public function clock(): SystemClock;
}
