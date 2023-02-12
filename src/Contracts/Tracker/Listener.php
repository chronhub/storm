<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

interface Listener
{
    /**
     * Get the event name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get the event priority
     *
     * @return int
     */
    public function priority(): int;

    /**
     * Get callable callback
     *
     * @return callable<Story>
     */
    public function story(): callable;
}
