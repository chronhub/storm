<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Workflow\Watcher\WatcherManager;

interface Subscriptor
{
    public function discoverStreams(): void;

    public function setContext(ContextReader $context, bool $allowRerun): void;

    public function getContext(): ?ContextReader;

    public function restoreUserState(): void;

    public function isUserStateInitialized(): bool;

    public function setStatus(ProjectionStatus $status): void;

    public function currentStatus(): ProjectionStatus;

    public function getProcessedStream(): string;

    public function setProcessedStream(string $streamName): void;

    public function setStreamIterator(?MergeStreamIterator $streamIterator): void;

    public function pullStreamIterator(): ?MergeStreamIterator;

    public function recognition(): CheckpointRecognition;

    public function watcher(): WatcherManager;

    public function receive(callable|object $event): mixed;

    public function option(): ProjectionOption;

    public function clock(): SystemClock;
}
