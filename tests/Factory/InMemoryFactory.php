<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Factory;

use Chronhub\Storm\Chronicler\InMemory\InMemoryEventStream;
use Chronhub\Storm\Chronicler\InMemory\StandaloneInMemoryChronicler;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Chronicler\InMemoryChronicler;
use Chronhub\Storm\Contracts\Projector\ProjectorManagerInterface;
use Chronhub\Storm\Contracts\Projector\SubscriptionFactory;
use Chronhub\Storm\Projector\Factory\InMemorySubscriptionFactory;
use Chronhub\Storm\Projector\Filter\InMemoryQueryScope;
use Chronhub\Storm\Projector\Options\InMemoryOption;
use Chronhub\Storm\Projector\ProjectorManager;
use Chronhub\Storm\Projector\Provider\InMemoryProjectionProvider;
use Chronhub\Storm\Projector\Support\ReadModel\InMemoryReadModel;
use Chronhub\Storm\Serializer\ProjectorJsonSerializer;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Illuminate\Events\Dispatcher;
use Symfony\Component\Uid\Uuid;

class InMemoryFactory
{
    protected string $eventStoreType = StandaloneInMemoryChronicler::class;

    protected InMemoryChronicler $eventStore;

    public InMemoryEventStream $eventStoreProvider;

    public InMemoryProjectionProvider $projectionProvider;

    public PointInTime $clock;

    public InMemoryReadModel $readModel;

    public function __construct()
    {
        $this->clock = new PointInTime();
        $this->eventStoreProvider = new InMemoryEventStream();
        $this->projectionProvider = new InMemoryProjectionProvider($this->clock);
        $this->readModel = new InMemoryReadModel();
    }

    public function getFactory(): SubscriptionFactory
    {
        $clock = new PointInTime();

        return new InMemorySubscriptionFactory(
            $this->getEventStore(),
            $this->projectionProvider,
            $this->eventStoreProvider,
            $clock,
            new ProjectorJsonSerializer(),
            new Dispatcher(),
            new InMemoryQueryScope(),
            new InMemoryOption(),
        );
    }

    public function getManager(): ProjectorManagerInterface
    {
        return new ProjectorManager($this->getFactory());
    }

    public function getEventStore(): InMemoryChronicler
    {
        $eventStore = $this->eventStoreType;

        return $this->eventStore ??= new $eventStore(
            $this->eventStoreProvider,
            new DetermineStreamCategory()
        );
    }

    public function getStream(
        string $streamName,
        int $numberOfEvents,
        ?string $eventTimeModifier = null,
        Uuid|string|null $eventId = null,
        string $eventType = SomeEvent::class,
        int $positionStartAt = 1
    ): Stream {
        $factory = StreamEventsFactory::withEvent($eventType);

        $streamEvents = $factory->timesWithHeaders($numberOfEvents, $eventTimeModifier, $eventId, $positionStartAt);

        return new Stream(new StreamName($streamName), $streamEvents);
    }
}
