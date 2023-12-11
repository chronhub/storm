<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleLoop;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\HandleStreamGap;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\MonitorRemoteStatus;
use Chronhub\Storm\Projector\Activity\PersistOrUpdate;
use Chronhub\Storm\Projector\Activity\RefreshProjection;
use Chronhub\Storm\Projector\Activity\ResetEventCounter;
use Chronhub\Storm\Projector\Activity\RisePersistentProjection;
use Chronhub\Storm\Projector\Activity\RunUntil;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Workflow;

trait InteractWithPersistentSubscription
{
    public function start(ContextReaderInterface $context, bool $keepRunning): void
    {
        if (! $context->queryFilter() instanceof ProjectionQueryFilter) {
            throw new RuntimeException('Persistent subscription requires a projection query filter.');
        }

        if ($context->keepState() === true) {
            throw new RuntimeException('Keep state is only available for query projection.');
        }

        $this->subscription->setContext($context, true);
        $this->subscription->setOriginalUserState();
        $this->subscription->sprint->runInBackground($keepRunning);
        $this->subscription->sprint->continue();

        $project = new RunProjection($this->newWorkflow(), $keepRunning, $this->management);
        $project->beginCycle();
    }

    public function getName(): string
    {
        return $this->management->getName();
    }

    public function getState(): array
    {
        return $this->subscription->state->get();
    }

    protected function newWorkflow(): Workflow
    {
        return new Workflow($this->subscription, $this->getActivities());
    }

    protected function getActivities(): array
    {
        $monitor = new MonitorRemoteStatus();

        return [
            new HandleLoop(),
            new RunUntil($this->subscription->clock, $this->subscription->context()->timer()),
            new RisePersistentProjection($monitor, $this->management),
            new LoadStreams(),
            new HandleStreamEvent(
                new EventProcessor($this->subscription->context()->reactors(), $this->getScope(), $this->management)
            ),
            new HandleStreamGap($this->management),
            new PersistOrUpdate($this->management),
            new ResetEventCounter(),
            new DispatchSignal(),
            new RefreshProjection($monitor, $this->management),
        ];
    }

    /**
     * @internal
     */
    abstract public function getScope(): ProjectorScope;
}
