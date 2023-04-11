<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Chronhub\Storm\Tests\UnitTestCase;
use Closure;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

#[CoversClass(Workflow::class)]
class WorkflowTest extends UnitTestCase
{
    private ProjectionManagement|MockObject $repository;

    private Subscription|MockObject $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscription = $this->createMock(Subscription::class);
        $this->repository = $this->createMock(ProjectionManagement::class);
    }

    public function testQueryWorkflow(): void
    {
        $sprint = new Sprint();
        $sprint->continue();
        $sprint->runInBackground(true);

        $this->subscription->expects($this->any())->method('sprint')->willReturn($sprint);

        $called = 0;

        $activities = $this->provideActivities($called);

        $workflow = (new Workflow($this->subscription, null))->through($activities);

        $inProgress = $workflow->process(fn (Subscription $subscription): bool => $subscription->sprint()->inProgress());

        $this->assertFalse($inProgress);
        $this->assertSame(2, $called);
    }

    public function testPersistentWorkflow(): void
    {
        $sprint = new Sprint();
        $sprint->continue();
        $sprint->runInBackground(true);

        $this->subscription->expects($this->any())->method('sprint')->willReturn($sprint);

        $called = 0;

        $activities = $this->provideActivities($called);

        $workflow = (new Workflow($this->subscription, $this->repository))->through($activities);

        $inProgress = $workflow->process(fn (Subscription $subscription): bool => $subscription->sprint()->inProgress());

        $this->assertFalse($inProgress);
        $this->assertSame(2, $called);
    }

    public function testReleaseLockOnExceptionRaisedByActivity(): void
    {
        $this->expectException(RuntimeException::class);

        $sprint = new Sprint();
        $sprint->continue();
        $sprint->runInBackground(true);

        $this->subscription->expects($this->any())->method('sprint')->willReturn($sprint);
        $this->repository->expects($this->once())->method('freed');

        $activities = [
            function (): void {
                throw new RuntimeException('error');
            },
        ];

        $workflow = (new Workflow($this->subscription, $this->repository))->through($activities);

        $workflow->process(fn (Subscription $subscription): bool => $subscription->sprint()->inProgress());
    }

    public function testRaiseOriginalExceptionWhenReleaseLockRaiseException(): void
    {
        $this->expectException(RuntimeException::class);
        $silentException = new InvalidArgumentException('fail silently');

        $sprint = new Sprint();
        $sprint->continue();
        $sprint->runInBackground(true);

        $this->subscription->expects($this->any())->method('sprint')->willReturn($sprint);
        $this->repository->expects($this->once())->method('freed')->willThrowException($silentException);

        $activities = [
            function (): void {
                throw new RuntimeException('error');
            },
        ];

        $workflow = (new Workflow($this->subscription, $this->repository))->through($activities);

        $workflow->process(fn (Subscription $subscription): bool => $subscription->sprint()->inProgress());
    }

    public function testItDoesNotTryToReleaseLockWhenProjectionAlreadyRunningIsRaised(): void
    {
        $this->expectException(ProjectionAlreadyRunning::class);

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

        $workflow = (new Workflow($this->subscription, $this->repository))->through($activities);

        $workflow->process(fn (Subscription $subscription): bool => $subscription->sprint()->inProgress());
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
}
