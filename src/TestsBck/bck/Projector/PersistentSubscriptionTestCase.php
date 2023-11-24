<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\EmitterProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Options\DefaultProjectionOption;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\StreamGapManager;
use Chronhub\Storm\Projector\Scheme\StreamManager;
use Chronhub\Storm\Projector\Subscription\AbstractPersistentSubscription;
use Chronhub\Storm\Projector\Subscription\EmitterSubscription;
use Chronhub\Storm\Projector\Subscription\ReadModelSubscription;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(AbstractPersistentSubscription::class)]
abstract class PersistentSubscriptionTestCase extends UnitTestCase
{
    protected ProjectionRepositoryInterface|MockObject $repository;

    protected ProjectorScope|MockObject $caster;

    protected DefaultProjectionOption $options;

    protected StreamManager $position;

    protected EventCounter $eventCounter;

    protected StreamGapManager $gap;

    protected PointInTime $clock;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProjectionRepositoryInterface::class);
        $this->options = new DefaultProjectionOption();
        $this->position = new StreamManager(new InMemoryEventStream());
        $this->clock = new PointInTime();
        $this->eventCounter = new EventCounter(100);
        $this->gap = new StreamGapManager($this->position, $this->clock, []);
    }

    public function testInstance(): void
    {
        $subscription = $this->newSubscription();

        $this->assertInstanceOf(PersistentSubscriptionInterface::class, $subscription);
        $this->assertInstanceOf(EventCounter::class, $subscription->eventCounter());
        $this->assertInstanceOf(StreamGapManager::class, $subscription->gap());
    }

    public function testRise(): void
    {
        $context = $this->defineContext();

        $subscription = $this->newSubscription();
        $subscription->compose($context, $this->caster, false);

        $this->repository->expects($this->once())->method('exists')->willReturn(false);
        $this->repository->expects($this->once())->method('create')->with(ProjectionStatus::IDLE);
        $this->repository->expects($this->once())->method('start');
        $this->repository->expects($this->once())->method('loadDetail')->willReturn(
            [['stream_name' => 25], ['counter' => 100]]
        );

        $this->assertEquals([], $subscription->streamManager()->jsonSerialize());
        $this->assertEquals(['counter' => 0], $subscription->state()->get());

        $subscription->rise();

        $this->assertEquals(['stream_name' => 25], $subscription->streamManager()->jsonSerialize());
        $this->assertEquals(['counter' => 100], $subscription->state()->get());

        $this->assertEquals(ProjectionStatus::RUNNING, $subscription->currentStatus());
    }

    public function testStore(): void
    {
        $context = $this->defineContext(10);

        $this->position->bind('stream_name', 25);

        $this->repository
            ->expects($this->once())
            ->method('persist')
            ->with(['stream_name' => 25], ['counter' => 10]);

        $subscription = $this->newSubscription();
        $subscription->compose($context, $this->caster, false);

        $subscription->store();
    }

    public function testClose(): void
    {
        $context = $this->defineContext(10);

        $this->position->bind('stream_name', 25);

        $this->repository
            ->expects($this->once())
            ->method('stop')
            ->with(['stream_name' => 25], ['counter' => 10]);

        $subscription = $this->newSubscription();
        $subscription->compose($context, $this->caster, false);
        $subscription->setStatus(ProjectionStatus::RUNNING);

        $this->assertTrue($subscription->sprint()->inProgress());

        $subscription->close();

        $this->assertEquals(ProjectionStatus::IDLE, $subscription->currentStatus());
        $this->assertFalse($subscription->sprint()->inProgress());
    }

    public function testRestart(): void
    {
        $context = $this->defineContext(10);

        $this->position->bind('stream_name', 25);

        $this->repository->expects($this->once())->method('startAgain');

        $subscription = $this->newSubscription();
        $subscription->compose($context, $this->caster, false);
        $subscription->setStatus(ProjectionStatus::IDLE);

        $this->assertTrue($subscription->sprint()->inProgress());

        $subscription->restart();

        $this->assertEquals(ProjectionStatus::RUNNING, $subscription->currentStatus());
        $this->assertTrue($subscription->sprint()->inProgress());
    }

    public function testBoundState(): void
    {
        $context = $this->defineContext();

        $subscription = $this->newSubscription();
        $subscription->compose($context, $this->caster, false);

        $this->repository->expects($this->once())->method('loadDetail')->willReturn(
            [['stream_name' => 25], ['counter' => 100]]
        );

        $this->assertEquals([], $subscription->streamManager()->jsonSerialize());
        $this->assertEquals(['counter' => 0], $subscription->state()->get());

        $subscription->synchronise();

        $this->assertEquals(['stream_name' => 25], $subscription->streamManager()->jsonSerialize());
        $this->assertEquals(['counter' => 100], $subscription->state()->get());
    }

    public function testRenew(): void
    {
        $context = $this->defineContext();

        $subscription = $this->newSubscription();
        $subscription->compose($context, $this->caster, false);

        $subscription->streamManager()->bind('stream_name', 25);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with(['stream_name' => 25])
            ->willReturn(true);

        $subscription->update();

        $this->assertEquals(['stream_name' => 25], $subscription->streamManager()->jsonSerialize());
    }

    public function testFreed(): void
    {
        $context = $this->defineContext();

        $subscription = $this->newSubscription();
        $subscription->setStatus(ProjectionStatus::RUNNING);
        $subscription->compose($context, $this->caster, false);

        $this->repository->expects($this->once())->method('release')->willReturn(true);

        $subscription->freed();

        $this->assertEquals(ProjectionStatus::IDLE, $subscription->currentStatus());
    }

    #[DataProvider('provideProjectionStatus')]
    public function testDisclose(ProjectionStatus $status): void
    {
        $context = $this->defineContext();

        $subscription = $this->newSubscription();
        $subscription->setStatus(ProjectionStatus::RUNNING);
        $subscription->compose($context, $this->caster, false);

        $this->repository->expects($this->once())->method('loadStatus')->willReturn($status);

        $this->assertEquals($status, $subscription->disclose());
    }

    public function testExceptionRaisedWhenQueryFilterNotSuited(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Persistent subscription require a projection query filter');

        $queryFilter = $this->createMock(QueryFilter::class);

        $context = new Context();
        $context->fromAll();
        $context->withQueryFilter($queryFilter);

        $subscription = $this->newSubscription();
        $subscription->compose($context, $this->caster, false);
    }

    public function testGetProjectionName(): void
    {
        $this->repository->expects($this->once())->method('projectionName')->willReturn('projection_name');

        $this->assertSame('projection_name', $this->newSubscription()->projectionName());
    }

    protected function testRevise(): void
    {
        $context = $this->defineContext();

        $this->repository
            ->expects($this->once())
            ->method('reset')
            ->with([], ['counter' => 0], ProjectionStatus::IDLE)
            ->willReturn(true);

        $subscription = $this->newSubscription();
        $subscription->compose($context, $this->caster, false);

        $subscription->state()->put(['counter' => 10]);

        $subscription->revise();

        $this->assertProjectionReset($subscription);
    }

    protected function testDiscard(bool $withEmittedEvent): void
    {
        $caster = $this->createMock(ReadModelProjectorScopeInterface::class);

        $context = $this->defineContext();

        $this->repository->expects($this->once())->method('delete');

        $subscription = $this->newSubscription();
        $subscription->compose($context, $caster, false);

        $subscription->state()->put(['counter' => 10]);

        $subscription->discard($withEmittedEvent);

        $this->assertProjectionReset($subscription);
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }

    public static function provideProjectionStatus(): Generator
    {
        yield [ProjectionStatus::IDLE];
        yield [ProjectionStatus::RUNNING];
        yield [ProjectionStatus::STOPPING];
        yield [ProjectionStatus::RESETTING];
        yield [ProjectionStatus::DELETING];
        yield [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS];
    }

    protected function defineContext(int $counter = 0): Context
    {
        $queryFilter = $this->createMock(ProjectionQueryFilter::class);

        $context = new Context();
        $context->initialize(fn (): array => ['counter' => $counter]);
        $context->fromAll();
        $context->withQueryFilter($queryFilter);

        return $context;
    }

    protected function newSubscription(): PersistentSubscriptionInterface
    {
        $subscriptionType = $this->defineSubscriptionType();

        if ($subscriptionType instanceof ReadModel) {
            $this->caster = $this->createMock(ReadModelProjectorScopeInterface::class);

            return new ReadModelSubscription(
                $this->repository, $this->options, $this->position,
                $this->eventCounter, $this->gap, $this->clock, $subscriptionType
            );
        }

        $this->caster = $this->createMock(EmitterProjectorScopeInterface::class);

        return new EmitterSubscription(
            $this->repository, $this->options, $this->position,
            $this->eventCounter, $this->gap, $this->clock, $subscriptionType
        );
    }

    private function assertProjectionReset(PersistentSubscriptionInterface $subscription): void
    {
        $this->assertEquals(ProjectionStatus::IDLE, $subscription->currentStatus());
        $this->assertSame(['counter' => 0], $subscription->state()->get());
        $this->assertSame([], $subscription->streamManager()->jsonSerialize());
    }

    abstract protected function defineSubscriptionType(): MockObject|ReadModel|Chronicler;
}
