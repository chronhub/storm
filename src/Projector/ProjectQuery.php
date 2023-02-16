<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\QueryCaster;
use Chronhub\Storm\Projector\Pipes\DispatchSignal;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Pipes\HandleStreamEvent;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Projector\Pipes\PrepareQueryRunner;
use Chronhub\Storm\Contracts\Projector\ProjectorCaster;

final readonly class ProjectQuery implements QueryProjector
{
    use InteractWithContext;

    public function __construct(protected Context $context,
                                private Chronicler $chronicler)
    {
    }

    public function run(bool $inBackground): void
    {
        $this->context->compose($this->getCaster(), $inBackground);

        $project = new RunProjection($this->pipes(), null);

        $project($this->context);
    }

    public function stop(): void
    {
        $this->context->runner->stop(true);
    }

    public function reset(): void
    {
        $this->context->streamPosition->reset();

        $this->context->resetStateWithInitialize();
    }

    public function getState(): array
    {
        return $this->context->state->get();
    }

    protected function getCaster(): ProjectorCaster
    {
        return new QueryCaster($this, $this->context->currentStreamName);
    }

    private function pipes(): array
    {
        return [
            new PrepareQueryRunner(),
            new HandleStreamEvent($this->chronicler, null),
            new DispatchSignal(),
        ];
    }
}
