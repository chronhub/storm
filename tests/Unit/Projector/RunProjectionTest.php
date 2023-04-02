<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Closure;
use RuntimeException;
use InvalidArgumentException;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\RunProjection;
use Chronhub\Storm\Projector\Scheme\Sprint;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;

#[CoversClass(RunProjection::class)]
final class RunProjectionTest extends UnitTestCase
{
    private ProjectionRepositoryInterface|MockObject $repository;

    private Subscription|MockObject $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ProjectionRepositoryInterface::class);
        $this->subscription = $this->createMock(Subscription::class);
    }

    public function testQueryProcessCycle(): void
    {
        $sprint = new Sprint();
        $sprint->continue();
        $sprint->runInBackground(true);

        $this->subscription->expects($this->any())
            ->method('sprint')
            ->willReturn($sprint);

        $called = 0;

        $activities = [
            function (Subscription $subscription, Closure $next) use (&$called): Closure|bool {
                $called++;

                $this->assertTrue($subscription->sprint()->inBackground());
                $this->assertTrue($subscription->sprint()->inProgress());

                return $next($subscription);
            },
            function (Subscription $subscription, Closure $next) use (&$called): Closure|bool {
                $called++;

                $this->assertTrue($subscription->sprint()->inProgress());

                $subscription->sprint()->stop();

                $this->assertFalse($subscription->sprint()->inProgress());

                return $next($subscription);
            },
        ];

        $runner = $this->createRunProjection($activities, null);

        $runner($this->subscription);

        $this->assertEquals(2, $called);
    }

    public function testPersistentProcessCycle(): void
    {
        $sprint = new Sprint();
        $sprint->continue();
        $sprint->runInBackground(true);

        $this->subscription->expects($this->any())
            ->method('sprint')
            ->willReturn($sprint);

        $this->repository->expects($this->once())->method('freed');

        $called = 0;

        $activities = [
            function (Subscription $subscription, Closure $next) use (&$called): Closure|bool {
                $called++;

                $this->assertTrue($subscription->sprint()->inBackground());
                $this->assertTrue($subscription->sprint()->inProgress());

                return $next($subscription);
            },
            function (Subscription $subscription, Closure $next) use (&$called): Closure|bool {
                $called++;

                $this->assertTrue($subscription->sprint()->inProgress());

                $subscription->sprint()->stop();

                $this->assertFalse($subscription->sprint()->inProgress());

                return $next($subscription);
            },
        ];

        $runner = $this->createRunProjection($activities, $this->repository);

        $runner($this->subscription);

        $this->assertEquals(2, $called);
    }

    public function testReleaseLockOnExceptionRaisedByActivity(): void
    {
        $this->expectException(RuntimeException::class);

        $sprint = new Sprint();
        $sprint->continue();
        $sprint->runInBackground(true);

        $this->subscription->expects($this->any())
            ->method('sprint')
            ->willReturn($sprint);

        $this->repository->expects($this->once())->method('freed');

        $activities = [
            function (): void {
                throw new RuntimeException('error');
            },
        ];

        $runner = $this->createRunProjection($activities, $this->repository);

        $runner($this->subscription);
    }

    public function testRaiseOriginalExceptionWhenReleaseLockRaiseException(): void
    {
        $this->expectException(RuntimeException::class);

        $sprint = new Sprint();
        $sprint->continue();
        $sprint->runInBackground(true);

        $this->subscription->expects($this->any())
            ->method('sprint')
            ->willReturn($sprint);

        $this->repository
            ->expects($this->once())
            ->method('freed')
            ->willThrowException(new InvalidArgumentException('fail silently'));

        $activities = [
            function (): void {
                throw new RuntimeException('error');
            },
        ];

        $runner = $this->createRunProjection($activities, $this->repository);

        $runner($this->subscription);
    }

    public function testItDoesNotTryToReleaseLockWhenProjectionAlreadyRunningIsRaised(): void
    {
        $this->expectException(RuntimeException::class);

        $sprint = new Sprint();
        $sprint->continue();
        $sprint->runInBackground(true);

        $this->subscription->expects($this->any())
            ->method('sprint')
            ->willReturn($sprint);

        $this->repository->expects($this->never())->method('freed');

        $activities = [
            function (): void {
                throw new ProjectionAlreadyRunning('Another projection is already running');
            },
        ];

        $runner = $this->createRunProjection($activities, $this->repository);

        $runner($this->subscription);
    }

    private function createRunProjection(array $activities, null|ProjectionRepositoryInterface|MockObject $repository): RunProjection
    {
        return new RunProjection($activities, $repository);
    }
}
