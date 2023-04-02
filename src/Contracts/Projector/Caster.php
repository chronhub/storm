<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Contracts\Clock\SystemClock;

interface Caster
{
    public function stop(): void;

    /**
     * @return string|null only null on setup but available at the first event
     */
    public function streamName(): ?string;

    public function clock(): SystemClock;
}
