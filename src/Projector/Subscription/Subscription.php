<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerDecorator;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\StreamCacheInterface;
use Chronhub\Storm\Contracts\Projector\StreamGapManager;
use Chronhub\Storm\Contracts\Projector\StreamManager;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\EmittedStream;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Closure;

use function is_array;

final class Subscription
{
    public readonly Sprint $sprint;

    public readonly Chronicler $chronicler;

    public readonly ProjectionStateInterface $state;

    public readonly ?ReadModel $readModel;

    public readonly ?EventCounter $eventCounter;

    public readonly ?StreamCacheInterface $streamCache;

    public readonly ?EmittedStream $emittedStream;

    private ?string $currentStreamName = null;

    private ?MergeStreamIterator $streamIterator = null;

    private ProjectionStatus $status = ProjectionStatus::IDLE;

    public function __construct(
        public readonly ContextReaderInterface $context,
        public readonly StreamManager|StreamGapManager $streamManager,
        public readonly SystemClock $clock,
        public readonly ProjectionOption $option,
        Chronicler $chronicler,
    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        $this->chronicler = $chronicler;
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
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

    public function setReadModel(ReadModel $readModel): void
    {
        $this->readModel = $readModel;
    }

    public function setEventCounter(EventCounter $eventCounter): void
    {
        $this->eventCounter = $eventCounter;
    }

    public function setEmittedStream(EmittedStream $emittedStream): void
    {
        $this->emittedStream = $emittedStream;
    }

    public function setStreamCache(StreamCacheInterface $streamCache): void
    {
        $this->streamCache = $streamCache;
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
     * Shortcut to discover streams from queries.
     */
    public function discoverStreams(): void
    {
        $this->streamManager->discover($this->context->queries());
    }

    public function hasGapDetection(): bool
    {
        return $this->streamManager instanceof StreamGapManager;
    }
}
