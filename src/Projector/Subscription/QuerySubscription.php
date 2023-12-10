<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\RiseQueryProjection;
use Chronhub\Storm\Projector\Activity\RunUntil;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Projector\Scheme\QueryAccess;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Stats;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Closure;

final readonly class QuerySubscription implements QuerySubscriber
{
    public function __construct(
        private Subscription $subscription,
        private QueryManagement $management,
    ) {
    }

    public function start(ContextReaderInterface $context, bool $keepRunning): void
    {
        // allow rerunning the projection from its current state
        // as restarting will reset the projection state
        // todo should be enabled by option or a specific projection, some catchup
        // runAgain(bool $keepRunning, bool $fromScratch): void
        // do we need this?
        //$state = $this->state()->get();
        // if ($state !== []) {
        //   this->subscription->state->put($state);
        // }

        $this->subscription->setContext($context);
        $this->subscription->setOriginalUserState();
        $this->subscription->sprint->runInBackground($keepRunning);
        $this->subscription->sprint->continue();

        $project = new RunProjection($this->newWorkflow(), $keepRunning, null);

        $project->beginCycle();
    }

    public function outputState(): array
    {
        return $this->subscription->state->get();
    }

    public function resets(): void
    {
        $this->subscription->streamManager->resets();

        $this->subscription->initializeAgain();
    }

    public function getScope(): QueryAccess
    {
        $userScope = $this->subscription->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new QueryAccess($this->management);
    }

    protected function newWorkflow(): Workflow
    {
        $activities = [
            new RunUntil(),
            new RiseQueryProjection(new Stats()),
            new LoadStreams(),
            new HandleStreamEvent(
                new EventProcessor($this->subscription->context()->reactors(), $this->getScope(), null)
            ),
            new DispatchSignal(),
        ];

        return new Workflow($this->subscription, $activities);
    }
}
