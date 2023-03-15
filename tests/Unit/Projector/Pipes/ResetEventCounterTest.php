<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Pipes;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Pipes\ResetEventCounter;
use Chronhub\Storm\Tests\Unit\Projector\Mock\ProvideMockContext;

#[CoversClass(ResetEventCounter::class)]

final class ResetEventCounterTest extends UnitTestCase
{
    use ProvideMockContext;

    #[Test]
    public function it_reset_event_counter(): void
    {
        $this->counter->expects($this->once())->method('reset');

        $context = $this->newContext();

        $pipe = new ResetEventCounter();

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }
}
