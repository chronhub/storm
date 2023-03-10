<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Pipes\HandleGap;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideMockContext;

#[CoversClass(HandleGap::class)]
final class HandleGapTest extends UnitTestCase
{
    use ProvideMockContext;

    #[Test]
    public function it_sleep_and_store_events_when_gap_is_detected(): void
    {
        $context = $this->newContext();

        $this->gap->method('hasGap')->willReturn(true);
        $this->gap->expects($this->once())->method('sleep');
        $this->repository->expects($this->once())->method('store');

        $pipe = new HandleGap($this->repository);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }
}
