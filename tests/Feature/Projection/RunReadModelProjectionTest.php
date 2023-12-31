<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Feature;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scope\ReadModelAccess;
use Chronhub\Storm\Projector\Workflow\HaltOn;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Tests\Factory\InMemoryFactory;
use Chronhub\Storm\Tests\Stubs\Double\AnotherEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $this->testFactory = new InMemoryFactory();
    $this->eventStore = $this->testFactory->getEventStore();
    $this->projectorManager = $this->testFactory->getManager();
    $this->fromIncludedPosition = $this->projectorManager->queryScope()->fromIncludedPosition();
});

$eventStore = function (string $name, int $num, ?string $eventId = null): Stream {
    $eventId = $eventId ?? Uuid::v4()->toRfc4122();

    $stream = $this->testFactory->getStream($name, $num, null, $eventId);

    $this->eventStore->firstCommit($stream);

    return $stream;
};

test('can run read model projection with scope', function () use ($eventStore) {
    // feed our event store
    $eventStore->call($this, 'user', 10);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope) use ($readModel): void {
            $scope->ack(SomeEvent::class)?->incrementState();

            expect($scope)
                ->toBeInstanceOf(ReadModelAccess::class)
                ->and($scope->clock())->toBeInstanceOf(PointInTime::class)
                ->and($scope->readModel())->toBe($readModel)
                ->and($scope->event())->toBeInstanceOf(SomeEvent::class);
        })->run(false);

    expect($projector->getState())->toBe(['count' => 10]);
});

test('raise exception when event was not acked when calling event', function () use ($eventStore) {
    // feed our event store
    $eventStore->call($this, 'user', 1);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope->event();
        })->run(false);
})->throws(RuntimeException::class, 'Event must be acked before returning it');

test('raise exception when event was not acked when calling stack event', function () use ($eventStore) {
    // feed our event store
    $eventStore->call($this, 'user', 10);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope
                ->incrementState()
                ->stack('insert', $scope->event()->header(Header::EVENT_ID), $scope->getState());
        })->run(false);
})->throws(RuntimeException::class, 'Event must be acked before returning it');

test('can run read model projection with new scope', function () use ($eventStore) {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $eventStore->call($this, 'user', 10, $eventId);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ?->incrementState()
                ->when(
                    $scope['count'] === 1,
                    fn () => $scope->stack('insert', $scope->event()->header(Header::EVENT_ID), $scope->getState()),
                    fn () => $scope->stack('update', $scope->event()->header(Header::EVENT_ID), 'count', $scope['count'])
                );
        })->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);
});

test('can run read model projection and count events', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    $eventId1 = Uuid::v4()->toRfc4122();
    $stream1 = $this->testFactory->getStream('foo', 5, null, $eventId1, AnotherEvent::class);
    $this->eventStore->firstCommit($stream1);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0, 'other' => 0])
        ->subscribeToStream('user', 'foo')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope
                ->ackOneOf(SomeEvent::class)
                ?->incrementState()
                ->when(
                    $scope['count'] === 1,
                    fn () => $scope->stack('insert', $scope->event()->header(Header::EVENT_ID), ['count' => $scope['count']]),
                    fn () => $scope->stack('update', $scope->event()->header(Header::EVENT_ID), 'count', $scope['count'])
                );

            ! $scope->isAcked() and $scope->incrementState('other');
        })->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10, 'other' => 5])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);
});

test('can stop read model projection', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ?->incrementState()
                ->when(
                    $scope['count'] === 1,
                    fn () => $scope->stack('insert', $scope->event()->header(Header::EVENT_ID), $scope->getState()),
                    fn () => $scope->stack('update', $scope->event()->header(Header::EVENT_ID), 'count', $scope['count'])
                )->stopWhen($scope['count'] === 7);
        })->run(false);

    expect($projector->getState())
        ->toBe(['count' => 7])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 7]]);
});

test('can reset read model projection', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ?->incrementState()
                ->when(
                    $scope['count'] === 1,
                    fn () => $scope->stack('insert', $scope->event()->header(Header::EVENT_ID), $scope->getState()),
                    fn () => $scope->stack('update', $scope->event()->header(Header::EVENT_ID), 'count', $scope['count'])
                );
        })->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);

    $projector->reset();

    expect($projector->getState())
        ->toBe(['count' => 0])
        ->and($readModel->getContainer())->toBe([]);

    $projector->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);
});

test('can delete read model projection with read model', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ?->incrementState()
                ->when(
                    $scope['count'] === 1,
                    fn () => $scope->stack('insert', $scope->event()->header(Header::EVENT_ID), $scope->getState()),
                    fn () => $scope->stack('update', $scope->event()->header(Header::EVENT_ID), 'count', $scope['count'])
                );
        })->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);

    $projector->delete(false);

    expect($projector->getState())
        ->toBe(['count' => 0])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);

    // run again to put the projection back
    $projector->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);
});

test('can delete read model projection without read model', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ?->incrementState()
                ->when(
                    $scope['count'] === 1,
                    fn () => $scope->stack('insert', $scope->event()->header(Header::EVENT_ID), $scope->getState()),
                    fn () => $scope->stack('update', $scope->event()->header(Header::EVENT_ID), 'count', $scope['count'])
                );
        })->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);

    $projector->delete(true);

    expect($projector->getState())
        ->toBe(['count' => 0])
        ->and($readModel->getContainer())->toBe([]);

    // run again to put the projection and the read model back
    $projector->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);
});

test('can rerun read model projection by catchup', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ?->incrementState()
                ->when(
                    $scope['count'] === 1,
                    fn () => $scope->stack('insert', $scope->event()->header(Header::EVENT_ID), $scope->getState()),
                    fn () => $scope->stack('update', $scope->event()->header(Header::EVENT_ID), 'count', $scope['count'])
                );
        })->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);

    $stream1 = $this->testFactory->getStream('user', 10, null, $eventId, SomeEvent::class, 11);
    $this->eventStore->amend($stream1);

    $projector->run(false);

    expect($projector->getState())
        ->toBe(['count' => 20])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 20]]);
});

test('11can run read model projection from many streams', function () {
    // fake data where debit event time is all less than credit event time
    $eventId = Uuid::v4()->toRfc4122();
    $stream1 = $this->testFactory->getStream('debit', 10, '-10 second', $eventId);
    $this->eventStore->firstCommit($stream1);

    $stream2 = $this->testFactory->getStream('credit', 10, '+ 50 seconds', $eventId, AnotherEvent::class);
    $this->eventStore->firstCommit($stream2);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('balance', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['some_event' => 0, 'another_event' => 0])
        ->subscribeToStream('debit', 'credit')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ?->incrementState('some_event')
                ->when(
                    $scope['some_event'] === 1,
                    fn () => $scope->stack('insert', $scope->event()->header(Header::EVENT_ID), ['count' => 1]),
                    fn () => $scope->stack('increment', $scope->event()->header(Header::EVENT_ID), 'count', 1),
                );

            $scope
                ->ack(AnotherEvent::class)
                ?->incrementState('another_event')
                ->stack('increment', $scope->event()->header(Header::EVENT_ID), 'count', 1);
        })->run(false);

    expect($projector->getState())->toBe(['some_event' => 10, 'another_event' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 20]]);
});

test('can run read model projection from many streams and sort events by ascending order', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream1 = $this->testFactory->getStream('debit', 1, '-50 second', $eventId);
    $stream2 = $this->testFactory->getStream('debit', 1, '-30 second', $eventId, SomeEvent::class, 2);
    $this->eventStore->firstCommit($stream1);
    $this->eventStore->amend($stream2);

    $stream3 = $this->testFactory->getStream('credit', 1, '-40 seconds', $eventId, AnotherEvent::class);
    $stream4 = $this->testFactory->getStream('credit', 1, '-20 seconds', $eventId, AnotherEvent::class, 2);
    $this->eventStore->firstCommit($stream3);
    $this->eventStore->amend($stream4);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('balance', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['order' => [], 'index' => 0])
        ->subscribeToStream('debit', 'credit')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ?->incrementState('index')
                ->updateState('order.'.$scope->streamName().'.'.$scope['index'], $scope->event()::class);

            $scope
                ->ack(AnotherEvent::class)
                ?->incrementState('index')
                ->updateState('order.'.$scope->streamName().'.'.$scope['index'], $scope->event()::class);
        })->run(false);

    expect($projector->getState()['order'])->toBe([
        'debit' => [1 => SomeEvent::class, 3 => SomeEvent::class],
        'credit' => [2 => AnotherEvent::class, 4 => AnotherEvent::class],
    ]);
});

test('can no stream event loaded', function () {
    $eventId = Uuid::v4()->toRfc4122();
    $stream1 = $this->testFactory->getStream('debit', 50, '+10 second', $eventId);
    $this->eventStore->firstCommit($stream1);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('balance', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('debit')
        ->haltOn(function (HaltOn $halt): HaltOn {
            return $halt->streamEventLimitReach(50);
        })
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (ReadModelAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ?->incrementState()
                ->when(
                    $scope['count'] === 1,
                    fn () => $scope->stack('insert', $scope->event()->header(Header::EVENT_ID), $scope->getState()),
                    fn () => $scope->stack('update', $scope->event()->header(Header::EVENT_ID), 'count', $scope['count'])
                );
        })->run(true);

    expect($projector->getState())->toBe(['count' => 50]);
});

test('raise exception when query filter is not a projection query filter', function () {
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    $projector
        ->subscribeToStream('user')
        ->withQueryFilter($this->createMock(QueryFilter::class))
        ->when(function (DomainEvent $event): void {
        })->run(false);
})->throws(RuntimeException::class, 'Persistent subscription requires a projection query filter');
