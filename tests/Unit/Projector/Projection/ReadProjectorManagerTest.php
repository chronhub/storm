<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Projection;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Projector\EmitterCasterInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorManagerInterface;
use Chronhub\Storm\Contracts\Projector\QueryCasterInterface;
use Chronhub\Storm\Projector\AbstractSubscriptionFactory;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\InMemorySubscriptionFactory;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\ProjectorManager;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(ProjectorManager::class)]
#[CoversClass(AbstractSubscriptionFactory::class)]
#[CoversClass(InMemorySubscriptionFactory::class)]
final class ReadProjectorManagerTest extends InMemoryProjectorManagerTestCase
{
    private StreamName $streamName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->streamName = new StreamName('balance');
    }

    public function testInstance(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $this->assertInstanceOf(ProjectorManagerInterface::class, $manager);
    }

    public function testReadFromProjectorManager(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));

        $aggregateId = V4AggregateId::create();
        $expectedEvents = 2;

        $this->feedEventStore($this->streamName, $aggregateId, $expectedEvents);

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projection = $manager->emitter('amount');
        $this->assertSame('amount', $projection->getStreamName());

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->fromStreams($this->streamName->name)
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->whenAny(function (SomeEvent $event, array $state) use ($manager): array {
                TestCase::assertInstanceOf(EmitterCasterInterface::class, $this);
                TestCase::assertTrue($manager->exists('amount'));
                TestCase::assertEquals(ProjectionStatus::RUNNING->value, $manager->statusOf('amount'));

                if ($state['count'] === 0) {
                    TestCase::assertEquals([], $manager->stateOf('amount'));
                    TestCase::assertEquals([], $manager->streamPositionsOf('amount'));
                    TestCase::assertEquals(['amount'], $manager->filterNamesByAscendantOrder('foo', 'bar', 'amount'));
                }

                if ($state['count'] === 1) {
                    TestCase::assertEquals(['count' => 1], $manager->stateOf('amount'));
                    TestCase::assertEquals(['balance' => 1], $manager->streamPositionsOf('amount'));

                    $manager->stop('amount');

                    return $state;
                }

                $state['count']++;

                return $state;
            })
            ->run(true);

        $this->assertEquals(1, $projection->getState()['count']);
    }

    public function testResetQueryProjection(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));

        $aggregateId = V4AggregateId::create();
        $expectedEvents = 2;

        $this->feedEventStore($this->streamName, $aggregateId, $expectedEvents);

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projection = $manager->emitter('amount');

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->fromStreams($this->streamName->name)
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var QueryCasterInterface $this */
                $state['count']++;

                if ($state['count'] === 2) {
                    $this->stop();
                }

                return $state;
            })
            ->run(true);

        $this->assertEquals(2, $projection->getState()['count']);

        $projection->reset();

        $this->assertEquals(0, $projection->getState()['count']);
    }

    public function testExceptionRaisedOnStopProjectionNotFound(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        try {
            $manager->stop('amount');
        } catch (Throwable $exception) {
            $this->assertInstanceOf(ProjectionFailed::class, $exception);
            $this->assertInstanceOf(ProjectionNotFound::class, $exception->getPrevious());
        }
    }

    public function testExceptionRaisedOnResetProjectionNotFound(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        try {
            $manager->reset('amount');
        } catch (Throwable $exception) {
            $this->assertInstanceOf(ProjectionFailed::class, $exception);
            $this->assertInstanceOf(ProjectionNotFound::class, $exception->getPrevious());
        }
    }

    public function testExceptionRaisedOnDeleteProjectionNotFound(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        try {
            $manager->delete('amount', false);
        } catch (Throwable $exception) {
            $this->assertInstanceOf(ProjectionFailed::class, $exception);
            $this->assertInstanceOf(ProjectionNotFound::class, $exception->getPrevious());
        }
    }

    public function testExceptionRaisedOnDeleteWithEmittedEventsProjectionNotFound(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        try {
            $manager->delete('amount', true);
        } catch (Throwable $exception) {
            $this->assertInstanceOf(ProjectionFailed::class, $exception);
            $this->assertInstanceOf(ProjectionNotFound::class, $exception->getPrevious());
        }
    }
}
