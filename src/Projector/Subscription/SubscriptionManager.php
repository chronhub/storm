<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\StreamManager;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Contracts\Projector\UserState;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Stream\EventStreamDiscovery;
use Chronhub\Storm\Projector\Subscription\Notification\AckedEventReset;
use Chronhub\Storm\Projector\Subscription\Notification\BatchStreamsReset;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointReset;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointUpdated;
use Chronhub\Storm\Projector\Subscription\Notification\EventIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\EventReset;
use Chronhub\Storm\Projector\Subscription\Notification\HasBatchStreams;
use Chronhub\Storm\Projector\Subscription\Notification\HasGap;
use Chronhub\Storm\Projector\Subscription\Notification\IsEventReset;
use Chronhub\Storm\Projector\Subscription\Notification\ShouldSleepOnGap;
use Chronhub\Storm\Projector\Subscription\Notification\SleepWhenEmptyBatchStreams;
use Chronhub\Storm\Projector\Subscription\Notification\SprintRunning;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\StatusChanged;
use Chronhub\Storm\Projector\Subscription\Notification\StatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Notification\StreamEventAcked;
use Chronhub\Storm\Projector\Subscription\Notification\StreamProcessed;
use Chronhub\Storm\Projector\Subscription\Notification\StreamsDiscovered;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateChanged;
use Chronhub\Storm\Projector\Subscription\Notification\UserStateReset;
use Chronhub\Storm\Projector\Support\BatchStreamsAware;
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
        private readonly BatchStreamsAware $batchStreamsAware
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

    public function userState(): UserState
    {
        return $this->userState;
    }

    public function sprint(): Sprint
    {
        return $this->sprint;
    }

    public function streamManager(): StreamManager
    {
        return $this->streamManager;
    }

    public function batchStreamsAware(): BatchStreamsAware
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

    public function &getStreamName(): string
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

    //    /**
    //     * @param class-string $eventClass
    //     */
    //    private function locateEventHandler(string $eventClass): Closure
    //    {
    //        $handlers = [
    //            StatusDisclosed::class => fn ($event) => $this->status = $event->newStatus,
    //            StatusChanged::class => fn ($event) => $this->status = $event->newStatus,
    //            UserStateChanged::class => fn ($event) => $this->userState = $event->userState,
    //            UserStateReset::class => fn ($event) => $this->setOriginalUserState(),
    //            StreamsDiscovered::class => fn ($event) => $this->discoverStreams(),
    //            HasBatchStreams::class => fn ($event) => $this->batchStreamsAware->hasLoadedStreams($event->hasBatchStreams),
    //            BatchStreamsReset::class => fn ($event) => $this->batchStreamsAware->reset(),
    //            SleepWhenEmptyBatchStreams::class => fn ($event) => $this->batchStreamsAware->sleep(),
    //            EventIncremented::class => fn ($event) => $this->events['total']++,
    //            EventReset::class => fn ($event) => $this->events['total'] = 0,
    //            IsEventReset::class => fn ($event) => $this->isEventReset(),
    //            SprintStopped::class => fn ($event) => $this->stop(),
    //            SprintRunning::class => fn ($event) => $this->continue(),
    //            //fixMe checkpoint added is not event
    //            CheckpointAdded::class => fn ($event) => $this->addCheckpoint($event->streamName, $event->streamPosition),
    //            CheckpointReset::class => fn ($event) => $this->resetCheckpoints(),
    //            CheckpointUpdated::class => fn ($event) => $this->updateCheckpoints($event->checkpoints),
    //            HasGap::class => fn ($event) => $this->hasGap(),
    //            ShouldSleepOnGap::class => fn ($event) => $this->sleepWhenGap(),
    //            StreamEventAcked::class => fn ($event) => $this->eventsAcked[] = $event->eventClass,
    //            AckedEventReset::class => fn ($event) => $this->eventsAcked = [],
    //            StreamProcessed::class => fn ($event) => $this->streamName = $event->streamName,
    //        ];
    //
    //        return $handlers[$eventClass] ?? throw new InvalidArgumentException('Unknown notification: '.$eventClass);
    //    }
}
