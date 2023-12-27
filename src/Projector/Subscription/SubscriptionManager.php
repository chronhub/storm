<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\StreamManager;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Contracts\Projector\UserState;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Stream\EventStreamDiscovery;
use Chronhub\Storm\Projector\Support\BatchObserver;
use Chronhub\Storm\Projector\Support\Loop;
use Chronhub\Storm\Projector\Workflow\EventCounter;
use Chronhub\Storm\Projector\Workflow\InMemoryUserState;
use Chronhub\Storm\Projector\Workflow\Sprint;
use Closure;

final class SubscriptionManager implements Subscriptor
{
    private ?string $streamName = null;

    private ?ContextReader $context = null;

    private array $eventsAcked = [];

    private ?MergeStreamIterator $streamIterator = null;

    private ProjectionStatus $status = ProjectionStatus::IDLE;

    private EventCounter $eventCounter;

    private UserState $userState;

    private Sprint $sprint;

    public function __construct(
        private readonly EventStreamDiscovery $streamDiscovery,
        private readonly StreamManager $streamManager,
        private readonly SystemClock $clock,
        private readonly ProjectionOption $option,
        private readonly Loop $loop,
        private readonly BatchObserver $batchStreamsAware
    ) {
        $this->eventCounter = new EventCounter($option->getBlockSize());
        $this->userState = new InMemoryUserState();
        $this->sprint = new Sprint();
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

    public function eventCounter(): EventCounter
    {
        return $this->eventCounter;
    }

    public function sprint(): Sprint
    {
        return $this->sprint;
    }

    public function streamManager(): StreamManager
    {
        return $this->streamManager;
    }

    public function batch(): BatchObserver
    {
        return $this->batchStreamsAware;
    }

    public function currentStatus(): ProjectionStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectionStatus $status): void
    {
        $this->status = $status;
    }

    public function userState(): UserState
    {
        return $this->userState;
    }

    public function setOriginalUserState(): void
    {
        $originalUserState = value($this->context->userState()) ?? [];

        $this->userState->put($originalUserState);
    }

    public function getUserState(): array
    {
        return $this->userState->get();
    }

    public function isUserStateInitialized(): bool
    {
        return $this->context->userState() instanceof Closure;
    }

    public function setStreamName(string $streamName): void
    {
        $this->streamName = $streamName;
    }

    public function getStreamName(): string
    {
        return $this->streamName;
    }

    public function setStreamIterator(MergeStreamIterator $streamIterator): void
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
            $eventStreams = $this->streamDiscovery->query($query);

            $this->streamManager->refreshStreams($eventStreams);
        });
    }

    public function ackedEvents(): array
    {
        return $this->eventsAcked;
    }

    public function ackEvent(string $event): void
    {
        $this->eventsAcked[] = $event;
    }

    public function resetAckedEvents(): void
    {
        $this->eventsAcked = [];
    }

    public function isRising(): bool
    {
        return $this->loop->isFirstLoop();
    }

    public function loop(): Loop
    {
        return $this->loop;
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
