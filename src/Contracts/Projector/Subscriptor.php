<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Support\AckedStreamObserver;
use Chronhub\Storm\Projector\Support\BatchStreamObserver;
use Chronhub\Storm\Projector\Workflow\EventCounter;
use Chronhub\Storm\Projector\Workflow\Loop;
use Chronhub\Storm\Projector\Workflow\Sprint;

interface Subscriptor
{
    public function receive(callable $event): mixed;

    public function eventCounter(): EventCounter;

    public function userState(): UserState;

    public function sprint(): Sprint;

    public function streamManager(): StreamManager;

    public function batch(): BatchStreamObserver;

    public function acked(): AckedStreamObserver;

    public function setContext(ContextReader $context, bool $allowRerun): void;

    public function getContext(): ?ContextReader;

    public function setOriginalUserState(): void;

    public function getProcessedStream(): string;

    public function setProcessedStream(string $streamName): void;

    public function setStatus(ProjectionStatus $status): void;

    public function currentStatus(): ProjectionStatus;

    public function option(): ProjectionOption;

    public function setStreamIterator(MergeStreamIterator $streamIterator): void;

    public function pullStreamIterator(): ?MergeStreamIterator;

    public function discoverStreams(): void;

    public function isUserStateInitialized(): bool;

    public function loop(): Loop;

    public function clock(): SystemClock;
}
