<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Projection;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScopeInterface;
use Chronhub\Storm\Projector\ProjectorManager;
use Chronhub\Storm\Projector\ProjectQuery;
use Chronhub\Storm\Projector\Subscription\AbstractSubscriptionFactory;
use Chronhub\Storm\Projector\Subscription\InMemorySubscriptionFactory;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProjectorManager::class)]
#[CoversClass(AbstractSubscriptionFactory::class)]
#[CoversClass(InMemorySubscriptionFactory::class)]
#[CoversClass(ProjectQuery::class)]
final class QuerySubscriptionManagerTest extends InMemoryProjectorManagerTestCase
{
    private StreamName $streamName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->streamName = new StreamName('balance');
    }

    public function testEventHandlers(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $this->feedEventStore($this->streamName, V4AggregateId::create(), 1);

        $subscription = $manager->newQuery();

        $subscription
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (DomainEvent $event): void {
                /** @var QueryProjectorScopeInterface $this */
                TestCase::assertSame($event::class, SomeEvent::class);
                TestCase::assertInstanceOf(QueryProjectorScopeInterface::class, $this);
                TestCase::assertSame('balance', $this->streamName());
                TestCase::assertInstanceOf(SystemClock::class, $this->clock());
            })
            ->run(false);
    }

    public function testQuerySubscription(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $this->feedEventStore($this->streamName, V4AggregateId::create(), 10);

        $subscription = $manager->newQuery();

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

        $this->feedEventStore($this->streamName, V4AggregateId::create(), 10);

        $subscription = $manager->newQuery();

        $subscription
            ->initialize(fn (): array => ['count' => 0])
            ->withQueryFilter($manager->queryScope()->fromIncludedPosition())
            ->fromStreams('balance')
            ->whenAny(function (SomeEvent $event, array $state): array {
                /** @var QueryProjectorScopeInterface $this */
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

        $this->feedEventStore($this->streamName, V4AggregateId::create(), 10);

        $subscription = $manager->newQuery();

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

        $this->feedEventStore($this->streamName, V4AggregateId::create(), 10);

        $subscription = $manager->newQuery();

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
}
