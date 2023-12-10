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
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
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
        if ($this->subscription->isContextInitialized()) {
            throw new RuntimeException('Use run again to rerun the projection.');
        }

        $this->subscription->setContext($context, false);
        $this->subscription->setOriginalUserState();
        $this->subscription->sprint->runInBackground($keepRunning);
        $this->subscription->sprint->continue();

        $project = new RunProjection($this->newWorkflow(), $keepRunning, null);

        $project->beginCycle();
    }

    public function startAgain(bool $keepRunning, bool $fromScratch): void
    {
        if (! $this->subscription->isContextInitialized()) {
            throw new RuntimeException('Run the projection first before running again.');
        }

        $previousState = $this->subscription->state->get();

        if ($fromScratch) {
            $this->resets();

            $previousState = [];
        }

        // keep the previous state in memory if not reset
        $this->subscription->state->put($previousState);

        $this->subscription->sprint->runInBackground($keepRunning);
        $this->subscription->sprint->continue();

        $project = new RunProjection($this->newWorkflow(), $keepRunning, $this->management);
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
