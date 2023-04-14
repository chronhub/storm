<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Projection;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Chronicler\InMemory\StandaloneInMemoryChronicler;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Projector\QueryCasterInterface;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Projector\AbstractSubscriptionFactory;
use Chronhub\Storm\Projector\InMemoryProjectionProvider;
use Chronhub\Storm\Projector\InMemoryQueryScope;
use Chronhub\Storm\Projector\InMemorySubscriptionFactory;
use Chronhub\Storm\Projector\Options\InMemoryProjectionOption;
use Chronhub\Storm\Projector\ProjectorManager;
use Chronhub\Storm\Projector\ProjectQuery;
use Chronhub\Storm\Reporter\DomainEvent;
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
#[CoversClass(ProjectQuery::class)]
final class QuerySubscriptionManagerTest extends UnitTestCase
{
    private SystemClock $clock;

    private EventStreamProvider $eventStreamProvider;

    private ProjectionProvider $projectionProvider;

    private Chronicler $eventStore;

    private StreamName $streamName;

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

    public function testEventHandlers(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $this->feedEventStore(1);

        $subscription = $manager->query();

        $subscription
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (DomainEvent $event): void {
                /** @var QueryCasterInterface $this */
                TestCase::assertSame($event::class, SomeEvent::class);
                TestCase::assertInstanceOf(QueryCasterInterface::class, $this);
                TestCase::assertSame('balance', $this->streamName());
                TestCase::assertInstanceOf(SystemClock::class, $this->clock());
            })
            ->run(false);
    }

    public function testQuerySubscription(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $this->feedEventStore(10);

        $subscription = $manager->query();

        $subscription
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertEquals(10, $subscription->getState()['count']);
    }

    public function testStopQuerySubscription(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $this->feedEventStore(10);

        $subscription = $manager->query();

        $subscription
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var QueryCasterInterface $this */
                $state['count']++;

                if ($state['count'] === 5) {
                    $this->stop();
                }

                return $state;
            })
            ->run(false);

        $this->assertEquals(5, $subscription->getState()['count']);
    }

    public function testQueryFilter(): void
    {
        $queryFilter = new class() implements InMemoryQueryFilter
        {
            public function apply(): callable
            {
                return static function (DomainEvent $event): ?DomainEvent {
                    $internalPosition = (int) $event->header(EventHeader::INTERNAL_POSITION);

                    return ($internalPosition > 7) ? $event : null;
                };
            }

            public function orderBy(): string
            {
                return 'desc';
            }
        };

        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $this->feedEventStore(10);

        $subscription = $manager->query();

        $subscription
            ->initialize(fn (): array => ['event_version' => []])
            ->withQueryFilter($queryFilter)
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                $state['event_version'][] = $event->header(EventHeader::AGGREGATE_VERSION);

                return $state;
            })
            ->run(false);

        $this->assertEquals([10, 9, 8], $subscription->getState()['event_version']);
    }

    public function testResetQuerySubscription(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $this->feedEventStore(10);

        $subscription = $manager->query();

        $subscription
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                $state['count']++;

                return $state;
            })
            ->run(false);

        $this->assertEquals(10, $subscription->getState()['count']);

        $subscription->reset();

        $this->assertEquals(0, $subscription->getState()['count']);
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

    private function feedEventStore(int $expectedEvents): void
    {
        $this->eventStreamProvider->createStream($this->streamName->name, null);

        $streamEvents = [];

        $i = 1;
        while ($i !== $expectedEvents + 1) {
            $streamEvents[] = SomeEvent::fromContent(['amount' => $i])->withHeader(
                EventHeader::AGGREGATE_VERSION, $i
            );

            $i++;
        }

        $this->eventStore->amend(new Stream($this->streamName, $streamEvents));
    }
}
