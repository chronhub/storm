<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Closure;

use function is_array;

trait InteractWithSubscription
{
    protected ?string $currentStreamName = null;

    protected ContextReaderInterface $context;

    protected ProjectionStatus $status = ProjectionStatus::IDLE;

    public function compose(ContextReaderInterface $context, ProjectorScope $projectorScope, bool $keepRunning): void
    {
        if ($this instanceof PersistentSubscriptionInterface && ! $context->queryFilter() instanceof ProjectionQueryFilter) {
            throw new RuntimeException('Persistent subscription must have a projection query filter');
        }

        $this->context = $context;

        $userState = $this->context->bindUserState($projectorScope);

        $this->state->put($userState);

        $this->context->bindReactors($projectorScope);

        $this->sprint->runInBackground($keepRunning);

        $this->sprint->continue();
    }

    public function initializeAgain(): void
    {
        $this->state->reset();

        $callback = $this->context->userState();

        if ($callback instanceof Closure) {
            $userState = $callback();

            if (is_array($userState)) {
                $this->state->put($userState);
            }
        }
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

    public function context(): ContextReaderInterface
    {
        return $this->context;
    }

    public function sprint(): Sprint
    {
        return $this->sprint;
    }

    public function state(): ProjectionStateInterface
    {
        return $this->state;
    }

    public function option(): ProjectionOption
    {
        return $this->option;
    }

    public function streamManager(): StreamManagerInterface
    {
        return $this->streamManager;
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
