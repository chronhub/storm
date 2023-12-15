<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
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
        $this->initializeContext($context, $keepRunning);

        $this->startProjection($keepRunning);
    }

    public function getState(): array
    {
        return $this->subscription->state->get();
    }

    public function resets(): void
    {
        $this->subscription->streamManager->resets();
        $this->subscription->initializeAgain();
    }

    public function getScope(): QueryProjectorScope
    {
        $userScope = $this->subscription->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this->subscription);
        }

        return new QueryAccess($this->management);
    }

    protected function newWorkflow(): Workflow
    {
        $activities = [
            new RunUntil($this->subscription->clock, $this->subscription->context()->timer()),
            new RiseQueryProjection(),
            new LoadStreams(),
            new HandleStreamEvent(
                new EventProcessor($this->subscription->context()->reactors(), $this->getScope(), null)
            ),
            new DispatchSignal(),
        ];

        return new Workflow($this->subscription, $activities, null);
    }

    private function initializeContext(ContextReaderInterface $context, bool $keepRunning): void
    {
        if (! $this->subscription->isContextInitialized()) {
            $this->subscription->setContext($context, true);

            $this->subscription->setOriginalUserState();
        }

        if ($this->subscription->context()->keepState() === true) {
            if (! $this->subscription->context()->userState() instanceof Closure) {
                throw new RuntimeException('Projection context is not initialized. Provide a closure to initialize user state');
            }
        } else {
            $this->subscription->setOriginalUserState();
        }

        $this->subscription->sprint->runInBackground($keepRunning);
        $this->subscription->sprint->continue();
    }

    private function startProjection(bool $keepRunning): void
    {
        $project = new RunProjection(
            $this->newWorkflow(), $this->subscription->looper,
            $this->subscription->metrics, $keepRunning
        );

        $project->beginCycle();
    }
}
