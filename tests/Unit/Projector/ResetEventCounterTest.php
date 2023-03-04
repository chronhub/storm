<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Pipes\ResetEventCounter;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideMockContext;

final class ResetEventCounterTest extends UnitTestCase
{
    use ProvideMockContext;

    /**
     * @test
     */
    public function it_reset_event_counter(): void
    {
        $this->counter->expects($this->once())->method('reset');

        $context = $this->newContext();

        $pipe = new ResetEventCounter();

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }
}
