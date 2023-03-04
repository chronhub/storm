<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Closure;
use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Projector\Scheme\Pipeline;
use PHPUnit\Framework\Attributes\DataProvider;

final class PipelineTest extends UnitTestCase
{
    private MockObject|Context $context;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_send_context_to_all_pipes(bool $return): void
    {
        $count = 0;

        $pipeline = new Pipeline();

        $result = $pipeline
            ->send($this->context)
            ->through([$this->providePipe($count), $this->providePipe($count), $this->providePipe($count)])
            ->then(fn (): bool => $return);

        $this->assertEquals(3, $count);
        $this->assertEquals($result, $return);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_return_early_and_does_not_reached_destination(bool $return): void
    {
        $count = 0;

        $pipeline = new Pipeline();

        $result = $pipeline
            ->send($this->context)
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
            $this->assertSame($context, $this->context);
            $count++;

            return $next($context);
        };
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
