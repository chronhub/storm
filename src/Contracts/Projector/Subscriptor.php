<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Support\Loop;

interface Subscriptor
{
    public function incrementEvent(): void;

    public function resetEvent(): void;

    public function isEventReset(): bool;

    public function isEventReached(): bool;

    public function hasGap(): bool;

    public function sleepWhenGap(): bool;

    public function setContext(ContextReader $context, bool $allowRerun): void;

    public function getContext(): ContextReader;

    public function initializeAgain(): void;

    public function setOriginalUserState(): void;

    public function isContextInitialized(): bool;

    public function setUserState(array $userState): void;

    public function getUserState(): array;

    public function resetUserState(): void;

    public function setStreamName(string $streamName): void;

    public function &getStreamName(): string;

    public function currentStatus(): ProjectionStatus;

    public function setStatus(ProjectionStatus $status): void;

    public function option(): ProjectionOption;

    public function setStreamIterator(MergeStreamIterator $streamIterator): void;

    public function pullStreamIterator(): ?MergeStreamIterator;

    public function discoverStreams(): void;

    public function isUserStateInitialized(): bool;

    public function checkPoints(): array;

    public function resetCheckpoint();

    public function clock(): SystemClock;

    public function chronicler(): Chronicler;

    // todo profiler for events / observer and notification
    public function ackEvent(string $eventType): void;

    public function resetAckedEvents(): void;

    public function ackedEvents(): array;

    public function hasEventAcked(): bool;

    public function addCheckpoint(string $streamName, int $position): bool;

    public function updateCheckpoints(array $checkpoints): void;

    public function isRunning(): bool;

    public function continue(): void;

    public function isStopped(): bool;

    public function stop(): void;

    public function runInBackground(bool $inBackground): void;

    public function inBackground(): bool;

    public function isFirstLoop(): bool;

    public function getActivityFactory(): ActivityFactory;

    public function loop(): Loop;
}
