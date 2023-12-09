<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\RiseQueryProjection;
use Chronhub\Storm\Projector\Activity\RunUntil;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Projector\Scheme\QueryProjectorScope;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Stats;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Closure;

final class QuerySubscription implements QuerySubscriber
{
    use InteractWithSubscription;

    public function __construct(protected readonly Beacon $manager)
    {
    }

    public function start(bool $keepRunning): void
    {
        // allow rerunning the projection from its current state
        // as restarting will reset the projection state
        // todo should be enabled by option or a specific projection, some catchup
        // do we need this?
        $state = $this->manager->state()->get();

        $this->manager->start($keepRunning);

        if ($state !== []) {
            //$this->subscription->state->put($state);
        }

        $project = new RunProjection($this->newWorkflow(), $this->manager->sprint, null);

        $project->beginCycle();
    }

    public function resets(): void
    {
        $this->manager->streamBinder->resets();

        $this->manager->initializeAgain();
    }

    public function getScope(): QueryProjectorScope
    {
        $userScope = $this->manager->context->userScope();

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
                new EventProcessor($this, $this->manager->context->reactors(), $this->getScope())
            ),
            new DispatchSignal(),
        ];

        return new Workflow($this->manager, $activities);
    }
}
