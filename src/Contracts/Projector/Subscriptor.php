<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Support\Loop;

interface Subscriptor
{
    public function receive(object $event): mixed;

    public function isEventReset(): bool;

    public function isEventReached(): bool;

    public function hasGap(): bool;

    public function sleepWhenGap(): bool;

    public function setContext(ContextReader $context, bool $allowRerun): void;

    public function getContext(): ?ContextReader;

    public function setOriginalUserState(): void;

    public function getUserState(): array;

    public function &getStreamName(): string;

    public function currentStatus(): ProjectionStatus;

    public function option(): ProjectionOption;

    public function setStreamIterator(MergeStreamIterator $streamIterator): void;

    public function pullStreamIterator(): ?MergeStreamIterator;

    public function discoverStreams(): void;

    public function isUserStateInitialized(): bool;

    public function checkPoints(): array;

    public function resetCheckpoints();

    public function clock(): SystemClock;

    public function ackedEvents(): array;

    public function addCheckpoint(string $streamName, int $position): bool;

    public function updateCheckpoints(array $checkpoints): void;

    public function isRunning(): bool;

    public function continue(): void;

    public function isStopped(): bool;

    public function stop(): void;

    public function runInBackground(bool $keepRunning): void;

    public function inBackground(): bool;

    public function isRising(): bool;

    public function getActivityFactory(): ActivityFactory;

    public function loop(): Loop;
}
