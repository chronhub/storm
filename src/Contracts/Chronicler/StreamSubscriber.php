<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Contracts\Tracker\Subscriber;

interface StreamSubscriber extends Subscriber
{
    public function attachToChronicler(EventableChronicler $chronicler): void;

    public function detachFromChronicler(EventableChronicler $chronicler): void;
}
