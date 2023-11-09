<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\Caster;
use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\PrepareQueryRunner;
use Chronhub\Storm\Projector\Activity\RunUntil;
use Chronhub\Storm\Projector\Scheme\CastQuery;
use Chronhub\Storm\Projector\Scheme\Workflow;

final readonly class ProjectQuery implements QueryProjector
{
    use InteractWithContext;

    public function __construct(
        protected Subscription $subscription,
        protected ContextInterface $context,
        private Chronicler $chronicler
    ) {
    }

    public function run(bool $inBackground): void
    {
        $this->subscription->compose($this->context, $this->getCaster(), $inBackground);

        $project = new RunProjection($this->subscription, $this->newWorkflow());

        $project->beginCycle();
    }

    public function stop(): void
    {
        $this->subscription->sprint()->stop();
    }

    public function reset(): void
    {
        $this->subscription->streamPosition()->reset();

        $this->subscription->initializeAgain();
    }

    public function getState(): array
    {
        return $this->subscription->state()->get();
    }

    protected function getCaster(): Caster
    {
        return new CastQuery(
            $this, $this->subscription->clock(), fn (): ?string => $this->subscription->currentStreamName()
        );
    }

    private function newWorkflow(): Workflow
    {
        $activities = [
            new RunUntil(),
            new PrepareQueryRunner(),
            new HandleStreamEvent(new LoadStreams($this->chronicler)),
            new DispatchSignal(),
        ];

        return new Workflow($this->subscription, $activities);
    }
}
