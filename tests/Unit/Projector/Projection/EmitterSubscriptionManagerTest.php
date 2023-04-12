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
use Chronhub\Storm\Projector\InMemoryProjectionProvider;
use Chronhub\Storm\Projector\InMemoryQueryScope;
use Chronhub\Storm\Projector\InMemorySubscriptionFactory;
use Chronhub\Storm\Projector\Options\InMemoryProjectionOption;
use Chronhub\Storm\Projector\ProjectEmitter;
use Chronhub\Storm\Projector\ProjectorManager;
use Chronhub\Storm\Serializer\ProjectorJsonSerializer;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProjectorManager::class)]
#[CoversClass(AbstractSubscriptionFactory::class)]
#[CoversClass(InMemorySubscriptionFactory::class)]
final class EmitterSubscriptionManagerTest extends UnitTestCase
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

        $projection = $manager->emitter('amount');
        $this->assertEquals($projection, $manager->emitter('amount'));
        $this->assertNotSame($projection, $manager->emitter('amount'));

        $this->assertSame(ProjectEmitter::class, $projection::class);
    }

    public function testEmitEvent(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));
        $this->assertFalse($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->eventStore->hasStream(new StreamName('amount')));

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
                UnitTestCase::assertSame('balance', $this->streamName());
                UnitTestCase::assertInstanceOf(PointInTime::class, $this->clock());

                $this->emit($event);

                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertEquals(2, $projection->getState()['count']);

        $this->assertTrue($this->projectionProvider->exists('amount'));
        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertTrue($this->eventStore->hasStream(new StreamName('amount')));
    }

    public function testLinkToNewStream(): void
    {
        $this->assertFalse($this->projectionProvider->exists('amount'));
        $this->assertFalse($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->eventStore->hasStream(new StreamName('amount')));

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

                $this->linkTo('amount_duplicate', $event);

                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertEquals(2, $projection->getState()['count']);

        $this->assertTrue($this->projectionProvider->exists('amount'));
        $this->assertTrue($this->eventStore->hasStream($this->streamName));
        $this->assertFalse($this->eventStore->hasStream(new StreamName('amount')));
        $this->assertTrue($this->eventStore->hasStream(new StreamName('amount_duplicate')));
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
