<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Projector\RunProjection;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideMockContext;

#[CoversClass(RunProjection::class)]
final class RunQueryProjectionTest extends UnitTestCase
{
    use ProvideMockContext;

    #[Test]
    public function it_run_query_projection(): void
    {
        $called = 0;
        $pipes = [
            function (Context $context, callable $next) use (&$called): bool {
                $called++;

                return $next($context);
            },
            function () use (&$called): bool {
                $called++;

                return false;
            },
        ];

        $runner = new RunProjection($pipes, null);

        $context = $this->newContext();
        $this->assertFalse($context->runner->inBackground());

        $runner($context);

        $this->assertFalse($context->runner->isStopped());
        $this->assertEquals(2, $called);
    }

    #[Test]
    public function it_keep_process_pipes_even_one_pipe_stop_process(): void
    {
        $called = 0;
        $pipes = [
            function (Context $context, callable $next) use (&$called): bool {
                $called++;

                return $next($context);
            },
            function (Context $context, callable $next): bool {
                $context->runner->stop(true);

                return $next($context);
            },
            function () use (&$called): bool {
                $called++;

                return false;
            },
        ];

        $runner = new RunProjection($pipes, null);

        $context = $this->newContext();
        $this->assertFalse($context->runner->inBackground());

        $runner($context);

        $this->assertTrue($context->runner->isStopped());
        $this->assertEquals(2, $called);
    }
}
