<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Projection;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Projector\ProjectorManager;
use Chronhub\Storm\Projector\ProjectReadModel;
use Chronhub\Storm\Projector\ReadModel\InMemoryReadModel;
use Chronhub\Storm\Projector\Subscription\AbstractSubscriptionFactory;
use Chronhub\Storm\Projector\Subscription\InMemorySubscriptionFactory;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProjectorManager::class)]
#[CoversClass(AbstractSubscriptionFactory::class)]
#[CoversClass(InMemorySubscriptionFactory::class)]
#[CoversClass(ProjectReadModel::class)]
final class ReadModelSubscriptionManagerTest extends InMemoryProjectorManagerTestCase
{
    private StreamName $streamName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->streamName = new StreamName('balance');
    }

    public function testInstance(): void
    {
        $this->assertFalse($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->projectionProvider->exists('read_balance'));

        $readModel = new InMemoryReadModel();
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $aggregateId = V4AggregateId::create();
        $expectedEvents = 1;

        $this->feedEventStore($this->streamName, $aggregateId, $expectedEvents);

        $projection = $manager->newReadModel('read_balance', $readModel);
        $this->assertSame('read_balance', $projection->getStreamName());

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelProjectorScopeInterface $this */
                UnitTestCase::assertInstanceOf(ReadModelProjectorScopeInterface::class, $this);
                UnitTestCase::assertSame('balance', $this->streamName());
                UnitTestCase::assertInstanceOf(SystemClock::class, $this->clock());

                $this->readModel()
                    ->stack('insert', $event->header(EventHeader::AGGREGATE_ID), ['balance' => $event->content['amount']]);

                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertTrue($this->projectionProvider->exists('read_balance'));
        $this->assertSame($expectedEvents, $projection->getState()['count']);
        $this->assertEquals(($expectedEvents * ($expectedEvents + 1)) / 2, $readModel->getContainer()[$aggregateId->toString()]['balance']);
    }

    public function testReadModelProjection(): void
    {
        $this->assertFalse($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->projectionProvider->exists('read_balance'));

        $readModel = new InMemoryReadModel();

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $aggregateId = V4AggregateId::create();
        $expectedEvents = 10;

        $this->feedEventStore($this->streamName, $aggregateId, $expectedEvents);

        $projection = $manager->newReadModel('read_balance', $readModel);

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelProjectorScopeInterface $this */
                if ($state['count'] === 0) {
                    $this->readModel()
                        ->stack('insert', $event->header(EventHeader::AGGREGATE_ID), ['balance' => $event->content['amount']]);
                } else {
                    $this->readModel()
                        ->stack(
                            'increment',
                            $event->header(EventHeader::AGGREGATE_ID),
                            'balance',
                            $event->content['amount']
                        );
                }

                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertTrue($this->projectionProvider->exists('read_balance'));
        $this->assertSame($expectedEvents, $projection->getState()['count']);
        $this->assertEquals(($expectedEvents * ($expectedEvents + 1)) / 2, $readModel->getContainer()[$aggregateId->toString()]['balance']);
    }

    public function testStopReadModelProjection(): void
    {
        $this->assertFalse($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->projectionProvider->exists('read_balance'));

        $readModel = new InMemoryReadModel();

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $aggregateId = V4AggregateId::create();
        $expectedEvents = 10;

        $this->feedEventStore($this->streamName, $aggregateId, $expectedEvents);

        $projection = $manager->newReadModel('read_balance', $readModel);

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelProjectorScopeInterface $this */
                if ($state['count'] === 0) {
                    $this->readModel()
                        ->stack('insert', $event->header(EventHeader::AGGREGATE_ID), ['balance' => $event->content['amount']]);
                } else {
                    $this->readModel()
                        ->stack(
                            'increment',
                            $event->header(EventHeader::AGGREGATE_ID),
                            'balance',
                            $event->content['amount']
                        );
                }

                $state['count']++;

                if ($state['count'] === 5) {
                    $this->stop();
                }

                return $state;
            })
            ->run(false);

        $expectedEvents = $expectedEvents / 2;

        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertTrue($this->projectionProvider->exists('read_balance'));
        $this->assertSame($expectedEvents, $projection->getState()['count']);
        $this->assertEquals(($expectedEvents * ($expectedEvents + 1)) / 2, $readModel->getContainer()[$aggregateId->toString()]['balance']);
    }

    public function testResetReadModelProjection(): void
    {
        $this->assertFalse($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->projectionProvider->exists('read_balance'));

        $readModel = new InMemoryReadModel();

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $aggregateId = V4AggregateId::create();
        $expectedEvents = 10;

        $this->feedEventStore($this->streamName, $aggregateId, $expectedEvents);

        $projection = $manager->newReadModel('read_balance', $readModel);

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelProjectorScopeInterface $this */
                if ($state['count'] === 0) {
                    $this->readModel()
                        ->stack('insert', $event->header(EventHeader::AGGREGATE_ID), ['balance' => $event->content['amount']]);
                } else {
                    $this->readModel()
                        ->stack(
                            'increment',
                            $event->header(EventHeader::AGGREGATE_ID),
                            'balance',
                            $event->content['amount']
                        );
                }

                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertTrue($this->projectionProvider->exists('read_balance'));
        $this->assertSame($expectedEvents, $projection->getState()['count']);
        $this->assertEquals(($expectedEvents * ($expectedEvents + 1)) / 2, $readModel->getContainer()[$aggregateId->toString()]['balance']);

        $projection->reset();

        $this->assertEmpty($readModel->getContainer());
        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertTrue($this->projectionProvider->exists('read_balance'));
    }

    public function testDeleteReadModelProjection(): void
    {
        $this->assertFalse($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->projectionProvider->exists('read_balance'));

        $readModel = new InMemoryReadModel();

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $aggregateId = V4AggregateId::create();
        $expectedEvents = 10;

        $this->feedEventStore($this->streamName, $aggregateId, $expectedEvents);

        $projection = $manager->newReadModel('read_balance', $readModel);

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelProjectorScopeInterface $this */
                if ($state['count'] === 0) {
                    $this->readModel()
                        ->stack('insert', $event->header(EventHeader::AGGREGATE_ID), ['balance' => $event->content['amount']]);
                } else {
                    $this->readModel()
                        ->stack(
                            'increment',
                            $event->header(EventHeader::AGGREGATE_ID),
                            'balance',
                            $event->content['amount']
                        );
                }

                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertTrue($this->projectionProvider->exists('read_balance'));
        $this->assertSame($expectedEvents, $projection->getState()['count']);
        $this->assertEquals(($expectedEvents * ($expectedEvents + 1)) / 2, $readModel->getContainer()[$aggregateId->toString()]['balance']);

        $projection->delete(false);

        $this->assertNotEmpty($readModel->getContainer());
        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->projectionProvider->exists('read_balance'));
    }

    public function testDeleteWithEmittedEventsReadModelProjection(): void
    {
        $this->assertFalse($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->projectionProvider->exists('read_balance'));

        $readModel = new InMemoryReadModel();

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $aggregateId = V4AggregateId::create();
        $expectedEvents = 10;

        $this->feedEventStore($this->streamName, $aggregateId, $expectedEvents);

        $projection = $manager->newReadModel('read_balance', $readModel);

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelProjectorScopeInterface $this */
                if ($state['count'] === 0) {
                    $this->readModel()
                        ->stack('insert', $event->header(EventHeader::AGGREGATE_ID), ['balance' => $event->content['amount']]);
                } else {
                    $this->readModel()
                        ->stack(
                            'increment',
                            $event->header(EventHeader::AGGREGATE_ID),
                            'balance',
                            $event->content['amount']
                        );
                }

                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertTrue($this->projectionProvider->exists('read_balance'));
        $this->assertSame($expectedEvents, $projection->getState()['count']);
        $this->assertEquals(($expectedEvents * ($expectedEvents + 1)) / 2, $readModel->getContainer()[$aggregateId->toString()]['balance']);

        $projection->delete(true);

        $this->assertEmpty($readModel->getContainer());
        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->projectionProvider->exists('read_balance'));
    }
}
