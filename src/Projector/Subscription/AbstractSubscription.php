<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\Caster;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\StreamManager;
use Closure;

use function is_array;

abstract class AbstractSubscription implements Subscription
{
    protected ?string $currentStreamName = null;

    protected ProjectionStatus $status = ProjectionStatus::IDLE;

    protected ContextInterface $context;

    protected readonly ProjectionStateInterface $state;

    protected readonly Sprint $sprint;

    public function __construct(
        protected readonly ProjectionOption $option,
        protected readonly StreamManager $streamManager,
        protected readonly SystemClock $clock,
    ) {
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
    }

    public function compose(ContextInterface $context, Caster $projectorCaster, bool $keepRunning): void
    {
        $this->context = $context;

        if ($this instanceof PersistentSubscriptionInterface && ! $this->context->queryFilter() instanceof ProjectionQueryFilter) {
            throw new InvalidArgumentException('Persistent subscription require a projection query filter');
        }

        $this->sprint->runInBackground($keepRunning);

        $this->sprint->continue();

        $this->cast($projectorCaster);
    }

    public function initializeAgain(): void
    {
        $this->state->reset();

        $callback = $this->context->initCallback();

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

    public function context(): ContextReader
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

    protected function cast(Caster $caster): void
    {
        $originalState = $this->context->castInitCallback($caster);

        $this->state->put($originalState);

        $this->context->castEventHandlers($caster);
    }
}
