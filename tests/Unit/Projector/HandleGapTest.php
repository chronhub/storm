<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Pipes\HandleGap;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;

final class HandleGapTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy;

    /**
     * @test
     */
    public function it_sleep_and_store_events_when_gap_is_detected(): void
    {
        $context = $this->newContext();

        $this->gap->hasGap()->willReturn(true)->shouldBeCalledonce();
        $this->gap->sleep()->shouldBeCalledonce();

        $this->repository->store()->shouldBeCalledonce();

        $pipe = new HandleGap($this->repository->reveal());

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }
}
