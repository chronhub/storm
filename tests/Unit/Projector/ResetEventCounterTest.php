<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Pipes\ResetEventCounter;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;

final class ResetEventCounterTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy;

    /**
     * @test
     */
    public function it_reset_event_counter(): void
    {
        $this->counter->reset()->shouldBeCalledOnce();

        $context = $this->newContext();

        $pipe = new ResetEventCounter();

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }
}
