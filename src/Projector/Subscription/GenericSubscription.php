<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\StreamManager;
use Closure;

use function is_array;

final class GenericSubscription implements Subscription
{
    private ?string $currentStreamName = null;

    private ProjectionStatus $status = ProjectionStatus::IDLE;

    private ContextReaderInterface $context;

    private readonly ProjectionStateInterface $state;

    private readonly Sprint $sprint;

    public function __construct(
        private readonly ProjectionOption $option,
        private readonly StreamManager $streamManager,
        private readonly SystemClock $clock,
        private readonly Chronicler $chronicler
    ) {
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
    }

    public function compose(ContextReaderInterface $context, ProjectorScope $projectorScope, bool $keepRunning): void
    {
        $this->context = $context;

        $this->sprint->runInBackground($keepRunning);

        $this->sprint->continue();

        $userState = $this->context->bindUserState($projectorScope);

        $this->state->put($userState);

        $this->context->bindReactors($projectorScope);
    }

    public function initializeAgain(): void
    {
        $this->state->reset();

        $callback = $this->context->userState();

        if ($callback instanceof Closure) {
            $state = $callback();

            if (is_array($state)) {
                $this->state->put($state);
            }
        }
    }

    public function &currentStreamName(): ?string
    {
        return $this->currentStreamName;
    }

    public function setCurrentStreamName(string $streamName): void
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

    public function context(): ContextInterface
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

    public function streamManager(): StreamManager
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
}
