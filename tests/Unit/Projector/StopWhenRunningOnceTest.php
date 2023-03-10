<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Projector\Pipes\StopWhenRunningOnce;
use Chronhub\Storm\Contracts\Projector\PersistentProjector;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideMockContext;

#[CoversClass(StopWhenRunningOnce::class)]

final class StopWhenRunningOnceTest extends UnitTestCase
{
    use ProvideMockContext;

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_stop_projection(bool $runInBackground, bool $isStopped, bool $expectResult): void
    {
        $projector = $this->createMock(PersistentProjector::class);

        $expectResult
            ? $projector->expects(self::once())->method('stop')
            : $projector->expects(self::never())->method('stop');

        $context = $this->newContext();

        $context->runner->runInBackground($runInBackground);

        if (! $runInBackground) {
            $this->assertEquals($isStopped, $context->runner->isStopped());
        }

        $pipe = new StopWhenRunningOnce($projector);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    public static function provideBoolean(): Generator
    {
        yield [true, true, false];
        yield [true, false, false];
        yield [false, false, true];
    }
}
