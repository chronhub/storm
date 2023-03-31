<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextRead;
use Chronhub\Storm\Contracts\Projector\ContextBuilder;
use Chronhub\Storm\Contracts\Projector\ProjectorCaster;

interface Subscription
{
    public function compose(ContextBuilder $context, ProjectorCaster $projectorCaster, bool $runInBackground): void;

    public function initializeAgain(): void;

    public function context(): ContextRead;
}
