<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Pipes\StopWhenRunningOnce;
use Chronhub\Storm\Contracts\Projector\PersistentProjector;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;

final class StopWhenRunningOnceTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy;

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_stop_projection(bool $runInBackground, bool $isStopped, bool $expectResult): void
    {
        $projector = $this->prophesize(PersistentProjector::class);

        $expectResult
            ? $projector->stop()->shouldBeCalledOnce()
            : $projector->stop()->shouldNotBeCalled();

        $context = $this->newContext();

        $context->runner->runInBackground($runInBackground);

        if (! $runInBackground) {
            $this->assertEquals($isStopped, $context->runner->isStopped());
        }

        $pipe = new StopWhenRunningOnce($projector->reveal());

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    public function provideBoolean(): Generator
    {
        yield [true, true, false];
        yield [true, false, false];
        yield [false, false, true];
    }
}
