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

final class OpProjectorManagerTest extends UnitTestCase
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

    public function testStopRunningProjection(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));

        $this->feedEventStore(V4AggregateId::create(), 2);

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projection = $manager->emitter('amount');

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->fromStreams($this->streamName->name)
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->whenAny(function (SomeEvent $event, array $state) use ($manager): array {
                UnitTestCase::assertInstanceOf(EmitterCasterInterface::class, $this);
                UnitTestCase::assertTrue($manager->exists('amount'));
                UnitTestCase::assertEquals(ProjectionStatus::RUNNING->value, $manager->statusOf('amount'));

                if ($state['count'] === 1) {
                    $manager->stop('amount');

                    return $state;
                }

                $state['count']++;

                return $state;
            })
            ->run(true);

        $this->assertEquals(1, $projection->getState()['count']);
    }

    public function testResetRunningProjection(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));

        $this->feedEventStore(V4AggregateId::create(), 2);

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projection = $manager->emitter('amount');

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->fromStreams($this->streamName->name)
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->whenAny(function (SomeEvent $event, array $state) use ($manager): array {
                UnitTestCase::assertInstanceOf(EmitterCasterInterface::class, $this);
                UnitTestCase::assertTrue($manager->exists('amount'));
                UnitTestCase::assertEquals(ProjectionStatus::RUNNING->value, $manager->statusOf('amount'));

                if ($state['count'] === 1) {
                    $manager->reset('amount');

                    UnitTestCase::assertEquals(ProjectionStatus::RESETTING->value, $manager->statusOf('amount'));

                    return $state;
                }

                $state['count']++;

                return $state;
            })
            ->run(false); // reset an in background projection will restart again the projection

        $this->assertSame(0, $projection->getState()['count']);
        $this->assertSame('idle', $manager->statusOf('amount'));

        // restart new Projection
        $projectionAgain = $manager->emitter('amount');
        $projectionAgain
            ->initialize(fn (): array => ['count' => 0])
            ->fromStreams($this->streamName->name)
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var EmitterCasterInterface $this */
                $state['count']++;

                if ($state['count'] === 2) {
                    $this->stop();
                }

                return $state;
            })
            ->run(false);

        $this->assertSame(2, $projectionAgain->getState()['count']);
    }

    public function testDeleteProjection(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));

        $this->feedEventStore(V4AggregateId::create(), 2);

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projection = $manager->emitter('amount');

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->fromStreams($this->streamName->name)
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->whenAny(function (SomeEvent $event, array $state) use ($manager): array {
                UnitTestCase::assertInstanceOf(EmitterCasterInterface::class, $this);
                UnitTestCase::assertTrue($manager->exists('amount'));
                UnitTestCase::assertEquals(ProjectionStatus::RUNNING->value, $manager->statusOf('amount'));

                if ($state['count'] === 1) {
                    $manager->delete('amount', false);

                    UnitTestCase::assertEquals(ProjectionStatus::DELETING->value, $manager->statusOf('amount'));

                    return $state;
                }

                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertSame(0, $projection->getState()['count']);
        $this->assertFalse($this->projectionProvider->exists('amount'));
        $this->assertTrue($this->eventStore->hasStream($this->streamName));
    }

    public function testDeleteProjectionWithEmittedEvents(): void
    {
        // $this->markTestSkipped('TODO: fix this test');
        $this->assertFalse($this->projectionProvider->exists('amount'));
        $this->assertFalse($this->eventStreamProvider->hasRealStreamName('link_to_amount'));

        $this->feedEventStore(V4AggregateId::create(), 2);

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projection = $manager->emitter('amount');

        $projection
            ->initialize(fn (): array => ['count' => 0])
            ->fromStreams($this->streamName->name)
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var EmitterCasterInterface $this */
                UnitTestCase::assertInstanceOf(EmitterCasterInterface::class, $this);

                $this->linkTo('link_to_amount', $event);

                $state['count']++;

                if ($state['count'] === 2) {
                    $this->stop();
                }

                return $state;
            })
            ->run(true);

        $this->assertSame(2, $projection->getState()['count']);
        $this->assertTrue($this->projectionProvider->exists('amount'));
        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertTrue($this->eventStore->hasStream(new StreamName('link_to_amount')));

        $manager->delete('amount', true);

        $this->assertEquals(ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value, $manager->statusOf('amount'));

        $projection->run(false);

        $this->assertSame(0, $projection->getState()['count']);
        $this->assertFalse($this->projectionProvider->exists('amount'));
        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertTrue($this->eventStore->hasStream(new StreamName('link_to_amount')));
    }

    public function testExceptionRaisedOnStateOfProjectionNotFound(): void
    {
        $this->expectException(ProjectionNotFound::class);

        $this->assertFalse($this->projectionProvider->exists('amount'));

        $manager = new ProjectorManager($this->createSubscriptionFactory());
        $manager->stateOf('amount');
    }

    public function testExceptionRaisedOnStatusOfProjectionNotFound(): void
    {
        $this->expectException(ProjectionNotFound::class);

        $this->assertFalse($this->projectionProvider->exists('amount'));

        $manager = new ProjectorManager($this->createSubscriptionFactory());
        $manager->statusOf('amount');
    }

    public function testExceptionRaisedOnStreamPositionOfProjectionNotFound(): void
    {
        $this->expectException(ProjectionNotFound::class);

        $this->assertFalse($this->projectionProvider->exists('amount'));

        $manager = new ProjectorManager($this->createSubscriptionFactory());
        $manager->streamPositionsOf('amount');
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
