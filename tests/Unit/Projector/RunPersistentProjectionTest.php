<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use RuntimeException;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\RunProjection;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideMockContext;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;

/**
 * @coversDefaultClass \Chronhub\Storm\Projector\RunProjection
 */
final class RunPersistentProjectionTest extends UnitTestCase
{
    use ProvideMockContext;

    /**
     * @test
     */
    public function it_run_persistent_projection(): void
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

        $this->repository->expects($this->once())->method('freed');

        $runner = new RunProjection($pipes, $this->repository);

        $context = $this->newContext();

        $runner($context);

        $this->assertEquals(2, $called);
    }

    /**
     * @test
     */
    public function it_does_not_release_projection_lock_on_projection_already_running_exception(): void
    {
        $this->expectException(ProjectionAlreadyRunning::class);

        $pipes = [
            function (): never {
                throw new ProjectionAlreadyRunning();
            },
        ];

        $this->repository->expects($this->never())->method('freed');

        $runner = new RunProjection($pipes, $this->repository);

        $context = $this->newContext();

        $runner($context);
    }

    /**
     * @test
     */
    public function it_try_release_lock_on_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $pipes = [
            function (): never {
                throw new RuntimeException('foo');
            },
        ];

        $this->repository->expects($this->once())->method('freed');

        $runner = new RunProjection($pipes, $this->repository);

        $context = $this->newContext();

        $runner($context);
    }

    /**
     * @test
     */
    public function it_try_release_lock_on_exception_and_keep_exception_on_releasing_lock_silent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('foo');

        $pipes = [
            function (): never {
                throw new RuntimeException('foo');
            },
        ];

        $onReleaseLockException = new ProjectionFailed('something went wrong');

        $this->repository->expects($this->once())->method('freed')->willThrowException($onReleaseLockException);

        $runner = new RunProjection($pipes, $this->repository);

        $context = $this->newContext();

        $runner($context);
    }
}
