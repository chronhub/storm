<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\CheckpointRecognition;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Stream\EventStreamDiscovery;
use Chronhub\Storm\Projector\Workflow\Watcher\WatcherManager;
use Closure;

final class SubscriptionManager implements Subscriptor
{
    private ?string $streamName = null;

    private ?ContextReader $context = null;

    private ?MergeStreamIterator $streamIterator = null;

    private ProjectionStatus $status = ProjectionStatus::IDLE;

    public function __construct(
        private readonly EventStreamDiscovery $eventStreamDiscovery,
        private readonly CheckpointRecognition $checkpointRecognition,
        private readonly SystemClock $clock,
        private readonly ProjectionOption $option,
        private readonly WatcherManager $monitor,
    ) {
    }

    public function receive(callable $event): mixed
    {
        return $event($this);
    }

    public function setContext(ContextReader $context, bool $allowRerun): void
    {
        if ($this->context !== null && ! $allowRerun) {
            throw new RuntimeException('Rerunning projection is not allowed');
        }

        $this->context = $context;
    }

    public function getContext(): ?ContextReader
    {
        return $this->context;
    }

    public function recognition(): CheckpointRecognition
    {
        return $this->checkpointRecognition;
    }

    public function monitor(): WatcherManager
    {
        return $this->monitor;
    }

    public function currentStatus(): ProjectionStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectionStatus $status): void
    {
        $this->status = $status;
    }

    public function restoreUserState(): void
    {
        $originalUserState = value($this->context->userState()) ?? [];

        $this->monitor->userState()->put($originalUserState);
    }

    public function isUserStateInitialized(): bool
    {
        return $this->context->userState() instanceof Closure;
    }

    public function setProcessedStream(string $streamName): void
    {
        $this->streamName = $streamName;
    }

    public function getProcessedStream(): string
    {
        return $this->streamName;
    }

    public function setStreamIterator(?MergeStreamIterator $streamIterator): void
    {
        $this->streamIterator = $streamIterator;
    }

    public function pullStreamIterator(): ?MergeStreamIterator
    {
        $streamIterator = $this->streamIterator;

        $this->streamIterator = null;

        return $streamIterator;
    }

    public function discoverStreams(): void
    {
        tap($this->context->queries(), function (callable $query): void {
            $eventStreams = $this->eventStreamDiscovery->query($query);

            $this->checkpointRecognition->refreshStreams($eventStreams);
        });
    }

    public function option(): ProjectionOption
    {
        return $this->option;
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }
}
