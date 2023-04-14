<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Projection;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Chronicler\InMemory\StandaloneInMemoryChronicler;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\EmitterCasterInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\ProjectorManagerInterface;
use Chronhub\Storm\Contracts\Projector\QueryCasterInterface;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Projector\AbstractSubscriptionFactory;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\InMemoryProjectionProvider;
use Chronhub\Storm\Projector\InMemoryQueryScope;
use Chronhub\Storm\Projector\InMemorySubscriptionFactory;
use Chronhub\Storm\Projector\Options\InMemoryProjectionOption;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\ProjectorManager;
use Chronhub\Storm\Serializer\ProjectorJsonSerializer;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProjectorManager::class)]
#[CoversClass(AbstractSubscriptionFactory::class)]
#[CoversClass(InMemorySubscriptionFactory::class)]
final class ReadProjectorManagerTest extends UnitTestCase
{
    private SystemClock $clock;

    private EventStreamProvider $eventStreamProvider;

    private ProjectionProvider $projectionProvider;

    private Chronicler $eventStore;

    private StreamName $streamName;

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

        $this->feedEventStore($aggregateId, $expectedEvents);

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

        $this->feedEventStore($aggregateId, $expectedEvents);

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
        $this->expectException(ProjectionNotFound::class);

        $this->assertFalse($this->projectionProvider->exists('amount'));

        $manager = new ProjectorManager($this->createSubscriptionFactory());
        $manager->stop('amount');
    }

    public function testExceptionRaisedOnResetProjectionNotFound(): void
    {
        $this->expectException(ProjectionNotFound::class);

        $this->assertFalse($this->projectionProvider->exists('amount'));

        $manager = new ProjectorManager($this->createSubscriptionFactory());
        $manager->reset('amount');
    }

    public function testExceptionRaisedOnDeleteProjectionNotFound(): void
    {
        $this->expectException(ProjectionNotFound::class);

        $this->assertFalse($this->projectionProvider->exists('amount'));

        $manager = new ProjectorManager($this->createSubscriptionFactory());
        $manager->delete('amount', false);
    }

    public function testExceptionRaisedOnDeleteWithEmittedEventsProjectionNotFound(): void
    {
        $this->expectException(ProjectionNotFound::class);

        $this->assertFalse($this->projectionProvider->exists('amount'));

        $manager = new ProjectorManager($this->createSubscriptionFactory());
        $manager->delete('amount', true);
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
}
