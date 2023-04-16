<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Projection;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Chronicler\InMemory\StandaloneInMemoryChronicler;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Projector\InMemoryProjectionProvider;
use Chronhub\Storm\Projector\InMemoryQueryScope;
use Chronhub\Storm\Projector\InMemorySubscriptionFactory;
use Chronhub\Storm\Projector\Options\InMemoryProjectionOption;
use Chronhub\Storm\Serializer\ProjectorJsonSerializer;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;

abstract class InMemoryProjectorManagerTestCase extends UnitTestCase
{
    protected SystemClock $clock;

    protected EventStreamProvider $eventStreamProvider;

    protected ProjectionProvider $projectionProvider;

    protected Chronicler $eventStore;

    protected function feedEventStore(StreamName $streamName, AggregateIdentity $aggregateId, int $expectedEvents): void
    {
        $this->eventStreamProvider->createStream($streamName->name, null);

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

        $this->eventStore->amend(new Stream($streamName, $streamEvents));
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
    }

    protected function createSubscriptionFactory(): InMemorySubscriptionFactory
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
