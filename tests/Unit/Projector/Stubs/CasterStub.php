<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Stubs;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\Caster;

final class CasterStub implements Caster
{
    public bool $isStopped = false;

    public function stop(): void
    {
        $this->isStopped = true;
    }

    public function streamName(): ?string
    {
        return 'stream_name';
    }

    public function clock(): SystemClock
    {
        return new PointInTime();
    }
}
