<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface Listener
{
    public function name(): string;

    public function priority(): int;

    /**
     * @return callable(Story)
     */
    public function story(): callable;
}
