<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerDecorator;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\GapDetection;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\StreamManager;
use Chronhub\Storm\Contracts\Projector\UserState;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Stream\EventStreamDiscovery;
use Chronhub\Storm\Projector\Support\EventCounter;
use Chronhub\Storm\Projector\Support\Loop;
use Chronhub\Storm\Projector\Workflow\InMemoryUserState;
use Chronhub\Storm\Projector\Workflow\Sprint;
use Closure;

use function is_array;

final class Subscription
{
    public readonly Sprint $sprint;

    public readonly Chronicler $chronicler;

    public readonly UserState $state;

    public readonly ?EventCounter $eventCounter; // @phpstan-ignore-line

    public readonly Loop $looper;

    private ?ContextReader $context = null;

    private ?string $currentStreamName = null;

    private ?MergeStreamIterator $streamIterator = null;

    private ProjectionStatus $status = ProjectionStatus::IDLE;

    public function __construct(
        private readonly EventStreamDiscovery $streamDiscovery,
        public readonly StreamManager|GapDetection $streamManager,
        public readonly SystemClock $clock,
        public readonly ProjectionOption $option,
        public readonly ?ActivityFactory $activityFactory,
        Chronicler $chronicler,

    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        $this->chronicler = $chronicler;
        $this->state = new InMemoryUserState();
        $this->sprint = new Sprint();
        $this->looper = new Loop();
    }

    public function setContext(ContextReader $context, bool $allowRerun): void
    {
        if ($this->context !== null && ! $allowRerun) {
            throw new RuntimeException('Rerunning projection is not allowed');
        }

        $this->context = $context;
    }

    public function context(): ContextReader
    {
        return $this->context;
    }

    public function isContextInitialized(): bool
    {
        return $this->context !== null;
    }

    public function &currentStreamName(): ?string
    {
        return $this->currentStreamName;
    }

    public function setStreamName(string &$streamName): void
    {
        $this->currentStreamName = &$streamName;
    }

    public function currentStatus(): ProjectionStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectionStatus $status): void
    {
        $this->status = $status;
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

    public function setEventCounter(EventCounter $eventCounter): void
    {
        $this->eventCounter = $eventCounter; // @phpstan-ignore-line
    }

    public function initializeAgain(): void
    {
        $this->state->reset();

        $this->setOriginalUserState();
    }

    public function setOriginalUserState(): void
    {
        $callback = $this->context->userState();

        if ($callback instanceof Closure) {
            $userState = $callback();

            if (is_array($userState)) {
                $this->state->put($userState);
            }
        } else {
            $this->state->put([]);
        }
    }

    /**
     * Discover streams from queries.
     */
    public function discoverStreams(): void
    {
        $queries = $this->context->queries();

        $eventStreams = $this->streamDiscovery->query($queries);

        $this->streamManager->refreshStreams($eventStreams);
    }

    /**
     * Check if the stream manager has gap detection.
     */
    public function hasGapDetection(): bool
    {
        return $this->streamManager instanceof GapDetection;
    }
}
