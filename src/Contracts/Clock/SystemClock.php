<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Clock;

use Symfony\Component\Clock\ClockInterface;

interface SystemClock extends ClockInterface
{
    public function getFormat(): string;
}
