<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\GapDetection;
use Chronhub\Storm\Contracts\Projector\Observer;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\StreamManager;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Stream\EventStreamDiscovery;
use Chronhub\Storm\Projector\Support\Loop;
use Closure;

use function is_array;

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

    private array $observers = [];

    private ?MergeStreamIterator $streamIterator = null;

    private ?ContextReader $context = null;

    private array $eventsAcked = [];

    public function __construct(
        private readonly EventStreamDiscovery $streamDiscovery,
        private readonly StreamManager $streamManager,
        private readonly SystemClock $clock,
        private readonly ProjectionOption $option,
        public readonly Loop $loop,
        private readonly ActivityFactory $activityFactory,
    ) {
    }

    public function attach(Observer $observer): void
    {
        $this->observers[] = $observer;
    }

    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    public function setContext(ContextReader $context, bool $allowRerun): void
    {
        if ($this->context !== null && ! $allowRerun) {
            throw new RuntimeException('Rerunning projection is not allowed');
        }

        $this->context = $context;
    }

    public function getContext(): ContextReader
    {
        return $this->context;
    }

    public function initializeAgain(): void
    {
        $this->resetUserState();

        $this->setOriginalUserState();
    }

    public function setOriginalUserState(): void
    {
        $callback = $this->context->userState();

        if ($callback instanceof Closure) {
            $userState = $callback();

            if (is_array($userState)) {
                $this->userState = $userState;
            }
        } else {
            $this->userState = [];
        }
    }

    public function isContextInitialized(): bool
    {
        return $this->context !== null;
    }

    public function setUserState(array $userState): void
    {
        $this->userState = $userState;
    }

    public function getUserState(): array
    {
        return $this->userState;
    }

    public function resetUserState(): void
    {
        $this->userState = [];
    }

    public function setStreamName(string $streamName): void
    {
        $this->streamName = &$streamName;
    }

    public function &getStreamName(): string
    {
        return $this->streamName;
    }

    public function currentStatus(): ProjectionStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectionStatus $status): void
    {
        $this->status = $status;
    }

    public function incrementEvent(): void
    {
        $this->events['total']++;
    }

    public function resetEvent(): void
    {
        $this->events['total'] = 0;
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
        $queries = $this->context->queries();

        $eventStreams = $this->streamDiscovery->query($queries);

        $this->streamManager->refreshStreams($eventStreams);
    }

    public function isUserStateInitialized(): bool
    {
        return $this->context->userState() instanceof Closure;
    }

    public function addCheckpoint(string $streamName, int $position): bool
    {
        return $this->streamManager->insert($streamName, $position);
    }

    public function updateCheckpoints(array $checkpoints): void
    {
        $this->streamManager->update($checkpoints);
    }

    public function checkPoints(): array
    {
        return $this->streamManager->checkpoints();
    }

    public function resetCheckpoint(): void
    {
        $this->streamManager->resets();
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }

    // todo profiler for events / observer and notification
    public function ackEvent(string $eventType): void
    {
        $this->eventsAcked[] = $eventType;
    }

    public function resetAckedEvents(): void
    {
        $this->eventsAcked = [];
    }

    public function ackedEvents(): array
    {
        return $this->eventsAcked;
    }

    public function hasEventAcked(): bool
    {
        return $this->eventsAcked !== [];
    }

    public function continue(): void
    {
        $this->sprint = true;
    }

    public function runInBackground(bool $inBackground): void
    {
        $this->keepRunning = $inBackground;
    }

    public function isRunning(): bool
    {
        return $this->sprint === true;
    }

    public function isStopped(): bool
    {
        return ! $this->isRunning();
    }

    public function stop(): void
    {
        $this->sprint = false;
    }

    public function inBackground(): bool
    {
        return $this->keepRunning;
    }

    public function isFirstLoop(): bool
    {
        return $this->loop->isFirstLoop();
    }

    public function getActivityFactory(): ActivityFactory
    {
        return $this->activityFactory;
    }

    public function loop(): Loop
    {
        return $this->loop;
    }
}
