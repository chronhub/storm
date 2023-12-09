<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerDecorator;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\RiseQueryProjection;
use Chronhub\Storm\Projector\Activity\RunUntil;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\QueryProjectorScope;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\Stats;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Closure;

final class QuerySubscription implements QuerySubscriber
{
    use InteractWithSubscription;

    public ProjectionStateInterface $state;

    public Sprint $sprint;

    public Chronicler $chronicler;

    protected SubscriptionHolder $holder;

    public function __construct(
        public ContextReaderInterface $context,
        public StreamManagerInterface $streamBinder,
        public SystemClock $clock,
        public ProjectionOption $option,
        Chronicler $chronicler,
    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        $this->chronicler = $chronicler;
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
        $this->holder = new SubscriptionHolder();
    }

    public function start(bool $keepRunning): void
    {
        // allow rerunning the projection from its current state
        // as restarting will reset the projection state
        // todo should be enabled by option or a specific projection, some catchup
        // do we need this?
        //$state = $this->state()->get();

        //        if ($state !== []) {
        //            //$this->subscription->state->put($state);
        //        }

        $this->setOriginalUserState();

        $this->sprint->runInBackground($keepRunning);

        $this->sprint->continue();

        $project = new RunProjection($this->newWorkflow(), $keepRunning, null);

        $project->beginCycle();
    }

    public function resets(): void
    {
        $this->streamBinder->resets();

        $this->initializeAgain();
    }

    public function getScope(): QueryProjectorScope
    {
        $userScope = $this->context->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new QueryProjectorScope($this);
    }

    protected function newWorkflow(): Workflow
    {
        $activities = [
            new RunUntil(),
            new RiseQueryProjection(new Stats()),
            new LoadStreams(),
            new HandleStreamEvent(
                new EventProcessor($this->context->reactors(), $this->getScope(), null)
            ),
            new DispatchSignal(),
        ];

        return new Workflow($this, $activities);
    }
}
