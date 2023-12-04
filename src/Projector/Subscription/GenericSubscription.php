<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerDecorator;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Closure;

use function is_array;

final class GenericSubscription implements Subscription
{
    private ?string $currentStreamName = null;

    private ?MergeStreamIterator $streamIterator = null;

    private ProjectionStatus $status = ProjectionStatus::IDLE;

    private readonly ProjectionStateInterface $state;

    private readonly Sprint $sprint;

    private readonly Chronicler $chronicler;

    public function __construct(
        private readonly ContextReaderInterface $context,
        private readonly StreamManagerInterface $streamManager,
        private readonly SystemClock $clock,
        private readonly ProjectionOption $option,
        Chronicler $chronicler,

    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        $this->chronicler = $chronicler;
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
    }

    public function start(bool $keepRunning): void
    {
        $this->setOriginalUserState();

        $this->sprint->runInBackground($keepRunning);

        $this->sprint->continue();
    }

    public function initializeAgain(): void
    {
        $this->state->reset();

        $this->setOriginalUserState();
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

    public function context(): ContextReaderInterface
    {
        return $this->context;
    }

    public function sprint(): Sprint
    {
        return $this->sprint;
    }

    public function option(): ProjectionOption
    {
        return $this->option;
    }

    public function streamManager(): StreamManagerInterface
    {
        return $this->streamManager;
    }

    public function state(): ProjectionStateInterface
    {
        return $this->state;
    }

    public function clock(): SystemClock
    {
        return $this->clock;
    }

    public function chronicler(): Chronicler
    {
        return $this->chronicler;
    }

    private function setOriginalUserState(): void
    {
        $callback = $this->context->userState();

        if ($callback instanceof Closure) {
            $userState = $callback();

            if (is_array($userState)) {
                $this->state->put($userState);
            }
        }
    }
}
