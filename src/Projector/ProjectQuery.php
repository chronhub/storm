<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\QuerySubscriptionInterface;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\RiseQueryProjection;
use Chronhub\Storm\Projector\Activity\RunUntil;
use Chronhub\Storm\Projector\Scheme\QueryProjectorScope;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Closure;

final readonly class ProjectQuery implements QueryProjector
{
    use InteractWithContext;

    public function __construct(
        protected QuerySubscriptionInterface $subscription,
    ) {
    }

    public function run(bool $inBackground): void
    {
        $this->subscription->compose($this->getScope(), $inBackground);

        $project = new RunProjection($this->subscription, $this->newWorkflow());

        $project->beginCycle();
    }

    public function stop(): void
    {
        $this->subscription->sprint()->stop();
    }

    public function reset(): void
    {
        $this->subscription->streamManager()->resets();

        $this->subscription->initializeAgain();
    }

    public function getState(): array
    {
        return $this->subscription->state()->get();
    }

    private function getScope(): ProjectorScope
    {
        $userScope = $this->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new QueryProjectorScope(
            $this, $this->subscription->clock(), fn (): string => $this->subscription->currentStreamName()
        );
    }

    private function newWorkflow(): Workflow
    {
        $activities = [
            new RunUntil(),
            new RiseQueryProjection(),
            new LoadStreams(),
            new HandleStreamEvent(),
            new DispatchSignal(),
        ];

        return new Workflow($this->subscription, $activities);
    }
}
