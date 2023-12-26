<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\GapDetection;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\StreamManager;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Stream\EventStreamDiscovery;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointAdded;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointReset;
use Chronhub\Storm\Projector\Subscription\Notification\CheckpointUpdated;
use Chronhub\Storm\Projector\Subscription\Notification\EventIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\EventReset;
use Chronhub\Storm\Projector\Subscription\Notification\HasBatchStreams;
use Chronhub\Storm\Projector\Subscription\Notification\HasGap;
use Chronhub\Storm\Projector\Subscription\Notification\IsEventReset;
use Chronhub\Storm\Projector\Subscription\Notification\ResetAckedEvent;
use Chronhub\Storm\Projector\Subscription\Notification\ResetBatchStreams;
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
use Closure;

final class SubscriptionManager implements Subscriptor
{
    private bool $keepRunning = false;

    private bool $sprint = false;

    private ?string $streamName = null;

    private ProjectionStatus $status = ProjectionStatus::IDLE;

    private array $userState = [];

    private array $events = [
        'total' => 0, // all stream events
        'acked' => 0, // all stream events acked
        'loaded' => false, // if event streams loaded
    ];

    private ?MergeStreamIterator $streamIterator = null;

    private ?ContextReader $context = null;

    private array $eventsAcked = [];

    public function __construct(
        private readonly EventStreamDiscovery $streamDiscovery,
        private readonly StreamManager $streamManager,
        private readonly SystemClock $clock,
        private readonly ProjectionOption $option,
        private readonly Loop $loop,
        private readonly BatchStreamsAware $batchStreamsAware
    ) {
    }

    public function receive(object $event): ?bool
    {
        if ($event instanceof CheckpointAdded) {
            return $this->addCheckpoint($event->streamName, $event->streamPosition);
        }

        if ($event instanceof HasGap) {
            return $this->hasGap();
        }

        if ($event instanceof ShouldSleepOnGap) {
            return $this->sleepWhenGap();
        }

        if ($event instanceof IsEventReset) {
            return $this->isEventReset();
        }

        match ($event::class) {
            StatusDisclosed::class, StatusChanged::class => $this->status = $event->newStatus,
            UserStateChanged::class => $this->userState = $event->userState,
            UserStateReset::class => $this->setOriginalUserState(),
            StreamsDiscovered::class => $this->discoverStreams(),
            HasBatchStreams::class => $this->batchStreamsAware->hasLoadedStreams($event->hasBatchStreams),
            ResetBatchStreams::class => $this->batchStreamsAware->reset(),
            SleepWhenEmptyBatchStreams::class => $this->batchStreamsAware->sleep(),
            StreamEventAcked::class => $this->eventsAcked[] = $event->eventClass,
            ResetAckedEvent::class => $this->eventsAcked = [],
            EventIncremented::class => $this->events['total']++,
            EventReset::class => $this->events['total'] = 0,
            SprintStopped::class => $this->stop(),
            SprintRunning::class => $this->continue(),
            CheckpointReset::class => $this->resetCheckpoints(),
            CheckpointUpdated::class => $this->updateCheckpoints($event->checkpoints),
            StreamProcessed::class => $this->streamName = $event->streamName,
            default => throw new RuntimeException('Unknown notification: '.$event::class),
        };

        return null;
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

    public function setOriginalUserState(): void
    {
        $this->userState = value($this->context->userState()) ?? [];
    }

    public function getUserState(): array
    {
        return $this->userState;
    }

    public function isUserStateInitialized(): bool
    {
        return $this->context->userState() instanceof Closure;
    }

    public function &getStreamName(): string
    {
        return $this->streamName;
    }

    public function currentStatus(): ProjectionStatus
    {
        return $this->status;
    }

    public function isEventReset(): bool
    {
        return $this->events['total'] === 0;
    }

    public function isEventReached(): bool
    {
        return $this->events['total'] === $this->option->getBlockSize();
    }

    public function option(): ProjectionOption
    {
        return $this->option;
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

    public function hasGap(): bool
    {
        if (! $this->streamManager instanceof GapDetection) {
            return false;
        }

        if (! $this->streamManager->hasGap()) {
            return false;
        }

        return true;
    }

    public function sleepWhenGap(): bool
    {
        if (! $this->hasGap()) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        $this->streamManager->sleepWhenGap();

        return true;
    }

    public function discoverStreams(): void
    {
        tap($this->context->queries(), function (callable $query): void {
            $eventStreams = $this->streamDiscovery->query($query);

            $this->streamManager->refreshStreams($eventStreams);
        });
    }

    public function addCheckpoint(string $streamName, int $position): bool
    {
        return $this->streamManager->insert($streamName, $position);
    }

    public function updateCheckpoints(array $checkpoints): void
    {
        $this->streamManager->update($checkpoints);
    }

    public function checkpoints(): array
    {
        return $this->streamManager->checkpoints();
    }

    public function resetCheckpoints(): void
    {
        $this->streamManager->resets();
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }

    public function ackedEvents(): array
    {
        return $this->eventsAcked;
    }

    public function continue(): void
    {
        $this->sprint = true;
    }

    public function runInBackground(bool $keepRunning): void
    {
        $this->keepRunning = $keepRunning;
    }

    public function isRunning(): bool
    {
        return $this->sprint;
    }

    public function stop(): void
    {
        $this->sprint = false;
    }

    public function inBackground(): bool
    {
        return $this->keepRunning;
    }

    public function isRising(): bool
    {
        return $this->loop->isFirstLoop();
    }

    public function loop(): Loop
    {
        return $this->loop;
    }
}
