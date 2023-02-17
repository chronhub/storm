<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface StreamSubscriber
{
    public function attachToChronicler(EventableChronicler $chronicler): void;

    public function detachFromChronicler(EventableChronicler $chronicler): void;
}
