<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\PrepareQueryRunner;
use Chronhub\Storm\Projector\Activity\RunUntil;
use Chronhub\Storm\Projector\Scheme\QueryProjectorScope;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Workflow;

final readonly class ProjectQuery implements QueryProjector
{
    use InteractWithContext;

    public function __construct(
        protected Subscription $subscription,
        protected ContextInterface $context,
    ) {
    }

    public function run(bool $inBackground): void
    {
        $this->subscription->compose($this->context, $this->getScope(), $inBackground);

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

    protected function getScope(): ProjectorScope
    {
        return new QueryProjectorScope(
            $this, $this->subscription->clock(), fn (): ?string => $this->subscription->currentStreamName()
        );
    }

    private function newWorkflow(): Workflow
    {
        $activities = [
            new RunUntil(),
            new PrepareQueryRunner(),
            new HandleStreamEvent(new LoadStreams($this->subscription->chronicler())),
            new DispatchSignal(),
        ];

        return new Workflow($this->subscription, $activities);
    }
}
