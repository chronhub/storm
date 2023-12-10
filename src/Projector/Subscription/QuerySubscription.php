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
        if (! $this->subscription->isContextInitialized()) {
            $this->subscription->setContext($context, true);
            $this->subscription->setOriginalUserState();
        } elseif (false === true) {
            // todo check if keepStateOnRerun in context is true
            //  otherwise, reset the state
        }

        // in short, when init with fn() =>['count' => 0],
        // running once will give loaded events will give ['count' => 10]
        // but running in this method again with no events loaded will give ['count' => 0] because the state is reset
        // but again, with 5 loaded events, it will give ['count' => 5]
        // up to dev to aggregate the state

        // instead, use keepStateOnRerun to keep the state from the last known position
        // will give ['count' => 10] and ['count' => 15] if 5 events loaded
        // and keep the state up to date

        // can always restart from scratch with resets() method

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
