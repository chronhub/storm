<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\RunProjection;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Tests\UnitTestCase;
use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

#[CoversClass(RunProjection::class)]
final class RunProjectionTest extends UnitTestCase
{
    private ProjectionManagement|MockObject $repository;

    private Subscription|MockObject $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ProjectionManagement::class);
        $this->subscription = $this->createMock(Subscription::class);
    }

    public function testQueryProcessCycle(): void
    {
        $sprint = new Sprint();
        $sprint->continue();
        $sprint->runInBackground(true);

        $this->subscription->expects($this->any())->method('sprint')->willReturn($sprint);

        $called = 0;

        $activities = $this->provideActivities($called);

        $runner = $this->createRunProjection($activities);

        $runner($this->subscription, null);

        $this->assertEquals(2, $called);
    }

    public function testPersistentProcessCycle(): void
    {
        $sprint = new Sprint();
        $sprint->continue();
        $sprint->runInBackground(true);

        $this->subscription->expects($this->any())->method('sprint')->willReturn($sprint);
        $this->repository->expects($this->once())->method('freed');

        $called = 0;

        $activities = $this->provideActivities($called);

        $runner = $this->createRunProjection($activities);

        $runner($this->subscription, $this->repository);

        $this->assertEquals(2, $called);
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

        $runner = $this->createRunProjection($activities);

        $runner($this->subscription, $this->repository);
    }

    private function provideActivities(int &$called): array
    {
        $called = 0;

        return [
            function (Subscription $subscription, ?ProjectionManagement $repository, Closure $next) use (&$called): Closure|bool {
                $called++;

                $this->assertTrue($subscription->sprint()->inBackground());
                $this->assertTrue($subscription->sprint()->inProgress());

                return $next($subscription, $repository);
            },
            function (Subscription $subscription, ?ProjectionManagement $repository, Closure $next) use (&$called): Closure|bool {
                $called++;

                $this->assertTrue($subscription->sprint()->inProgress());

                $subscription->sprint()->stop();

                $this->assertFalse($subscription->sprint()->inProgress());

                return $next($subscription, $repository);
            },
        ];
    }

    private function createRunProjection(array $activities): RunProjection
    {
        return new RunProjection($activities);
    }
}
