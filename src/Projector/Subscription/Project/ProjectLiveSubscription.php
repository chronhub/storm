<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription\Project;

use Chronhub\Storm\Projector\RunProjection;
use Chronhub\Storm\Projector\Scheme\QueryCaster;
use Chronhub\Storm\Projector\InteractWithContext;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Contracts\Projector\ContextBuilder;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\ProjectorCaster;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\PrepareQueryRunner;

final readonly class ProjectLiveSubscription implements QueryProjector
{
    use InteractWithContext;

    public function __construct(
       protected Subscription $subscription,
       protected ContextBuilder $context,
       private Chronicler $chronicler)
    {
    }

     public function run(bool $inBackground): void
     {
         $this->subscription->compose($this->context, $this->getCaster(), $inBackground);

         $project = new RunProjection($this->activities(), null);

         $project($this->subscription);
     }

     public function stop(): void
     {
         $this->subscription->runner->stop(true);
     }

     public function reset(): void
     {
         $this->subscription->streamPosition->reset();

         $this->subscription->initializeAgain();
     }

     public function getState(): array
     {
         return $this->subscription->state->get();
     }

     protected function getCaster(): ProjectorCaster
     {
         return new QueryCaster($this, $this->subscription->clock, $this->subscription->currentStreamName);
     }

     private function activities(): array
     {
         return [
             new PrepareQueryRunner(),
             new HandleStreamEvent($this->chronicler, null),
             new DispatchSignal(),
         ];
     }
}
