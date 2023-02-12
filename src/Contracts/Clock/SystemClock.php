<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Clock;

use Symfony\Component\Clock\ClockInterface;

interface SystemClock extends ClockInterface
{
    /**
     * Return format
     *
     * @return string
     */
    public function getFormat(): string;
}
