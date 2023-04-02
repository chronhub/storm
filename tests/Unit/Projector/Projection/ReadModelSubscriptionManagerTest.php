<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Projection;

use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Projector\ProjectorManager;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\InMemoryQueryScope;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Serializer\ProjectorJsonSerializer;
use Chronhub\Storm\Projector\InMemoryProjectionProvider;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Projector\AbstractSubscriptionFactory;
use Chronhub\Storm\Projector\InMemorySubscriptionFactory;
use Chronhub\Storm\Projector\ReadModel\InMemoryReadModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Projector\Options\InMemoryProjectionOption;
use Chronhub\Storm\Contracts\Projector\ReadModelCasterInterface;
use Chronhub\Storm\Chronicler\InMemory\StandaloneInMemoryChronicler;

final class ReadModelSubscriptionManagerTest extends UnitTestCase
{
    private SystemClock $clock;

    private EventStreamProvider $eventStreamProvider;

    private ProjectionProvider $projectionProvider;

    private Chronicler $eventStore;

    private StreamName $streamName;

    public function testInstance(): void
    {
        $this->assertFalse($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->projectionProvider->exists('read_balance'));

        $readModel = new InMemoryReadModel();
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $aggregateId = V4AggregateId::create();
        $expectedEvents = 1;

        $this->feedEventStore($aggregateId, $expectedEvents);

        $projection = $manager->readModel('read_balance', $readModel);

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelCasterInterface $this */
                UnitTestCase::assertInstanceOf(ReadModelCasterInterface::class, $this);
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

        $this->feedEventStore($aggregateId, $expectedEvents);

        $projection = $manager->readModel('read_balance', $readModel);

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelCasterInterface $this */
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

        $this->feedEventStore($aggregateId, $expectedEvents);

        $projection = $manager->readModel('read_balance', $readModel);

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelCasterInterface $this */
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

        $this->feedEventStore($aggregateId, $expectedEvents);

        $projection = $manager->readModel('read_balance', $readModel);

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelCasterInterface $this */
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

        $this->feedEventStore($aggregateId, $expectedEvents);

        $projection = $manager->readModel('read_balance', $readModel);

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelCasterInterface $this */
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

        $this->feedEventStore($aggregateId, $expectedEvents);

        $projection = $manager->readModel('read_balance', $readModel);

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var ReadModelCasterInterface $this */
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = new PointInTime();
        $this->eventStreamProvider = new InMemoryEventStream();
        $this->projectionProvider = new InMemoryProjectionProvider($this->clock);
        $this->eventStore = new StandaloneInMemoryChronicler(
            $this->eventStreamProvider,
            new DetermineStreamCategory()
        );

        $this->streamName = new StreamName('balance');
    }

    private function createSubscriptionFactory(): AbstractSubscriptionFactory
    {
        return new InMemorySubscriptionFactory(
            $this->eventStore,
            $this->projectionProvider,
            $this->eventStreamProvider,
            new InMemoryQueryScope(),
            $this->clock,
            new AliasFromClassName(),
            new ProjectorJsonSerializer(),
            new InMemoryProjectionOption(),
        );
    }

    private function feedEventStore(AggregateIdentity $aggregateId, int $expectedEvents): void
    {
        $this->eventStreamProvider->createStream($this->streamName->name, null);

        $streamEvents = [];

        $i = 1;
        while ($i !== $expectedEvents + 1) {
            $streamEvents[] = SomeEvent::fromContent(['amount' => $i])
                ->withHeader(Header::EVENT_TIME, $this->clock->now()->format($this->clock->getFormat()))
                ->withHeader(EventHeader::AGGREGATE_ID, $aggregateId->toString())
                ->withHeader(EventHeader::AGGREGATE_ID_TYPE, $aggregateId::class)
                ->withHeader(EventHeader::AGGREGATE_VERSION, $i);

            $i++;
        }

        $this->eventStore->amend(new Stream($this->streamName, $streamEvents));
    }
}
