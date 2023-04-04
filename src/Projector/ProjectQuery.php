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
use Chronhub\Storm\Projector\Activity\PrepareQueryRunner;
use Chronhub\Storm\Projector\Scheme\CastQuery;

final readonly class ProjectQuery implements QueryProjector
{
    use InteractWithContext;

    public function __construct(
       protected Subscription $subscription,
       protected ContextInterface $context,
       private Chronicler $chronicler)
    {
    }

     public function run(bool $inBackground): void
     {
         $this->subscription->compose($this->context, $this->getCaster(), $inBackground);

         $this->subscription->sprint()->continue();

         $project = new RunProjection($this->activities(), null);

         $project($this->subscription);
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
             $this, $this->subscription->clock(), $this->subscription->currentStreamName
         );
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
