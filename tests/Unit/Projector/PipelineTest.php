<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Closure;
use Generator;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\Pipeline;

final class PipelineTest extends ProphecyTestCase
{
    private ObjectProphecy|Context $context;

    protected function setUp(): void
    {
        $this->context = $this->prophesize(Context::class);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_send_context_to_all_pipes(bool $return): void
    {
        $count = 0;

        $pipeline = new Pipeline();

        $result = $pipeline
            ->send($this->context->reveal())
            ->through([$this->providePipe($count), $this->providePipe($count), $this->providePipe($count)])
            ->then(fn (): bool => $return);

        $this->assertEquals(3, $count);
        $this->assertEquals($result, $return);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_return_early_and_does_not_reached_destination(bool $return): void
    {
        $count = 0;

        $pipeline = new Pipeline();

        $result = $pipeline
            ->send($this->context->reveal())
            ->through([
                $this->providePipe($count),
                fn (): false => false,
                $this->providePipe($count)])
            ->then(fn (): bool => $return);

        $this->assertEquals(1, $count);
        $this->assertFalse($result);
    }

    private function providePipe(int &$count): Closure
    {
        return function (Context $context, Closure $next) use (&$count): callable|bool {
            $this->assertSame($context, $this->context->reveal());
            $count++;

            return $next($context);
        };
    }

    public function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
