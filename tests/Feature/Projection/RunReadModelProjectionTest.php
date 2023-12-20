<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Feature;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\ReadModelScope;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scope\ReadModelAccess;
use Chronhub\Storm\Reporter\DomainEvent;
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

/**
 * checkMe: do not use the same prefix for test/it
 * to expect only one test to run when it's above all other tests
 */
test('can run read model projection from one stream', function () {
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
        ->fromStreams('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (DomainEvent $event, array $state, ReadModelScope $scope) use ($readModel): array {
            if ($state['count'] === 1) {
                expect($scope)
                    ->toBeInstanceOf(ReadModelAccess::class)
                    ->and($scope->clock())->toBeInstanceOf(PointInTime::class)
                    ->and($scope->readModel())->toBe($readModel)
                    ->and($event)->toBeInstanceOf(SomeEvent::class);

                $scope->readModel()->stack('insert', $event->header(Header::EVENT_ID), $event->toContent());
            } else {
                $scope->readModel()->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);
            }

            $state['count']++;

            expect($scope->streamName())->toBe('user');

            return $state;
        })->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);
});

test('can run read model with shortcut stack from scope', function () {
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
        ->fromStreams('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (DomainEvent $event, array $state, ReadModelScope $scope): array {
            $state['count']++;

            $state['count'] === 1
                ? $scope->stack('insert', $event->header(Header::EVENT_ID), $event->toContent())
                : $scope->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);

            return $state;
        })->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10])
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
        ->fromStreams('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (DomainEvent $event, array $state, ReadModelScope $scope): array {
            if ($state['count'] === 1) {
                $scope->readModel()->stack('insert', $event->header(Header::EVENT_ID), $event->toContent());
            } else {
                $scope->readModel()->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);
            }

            $state['count']++;

            if ($state['count'] === 7) {
                $scope->stop();
            }

            return $state;
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
        ->fromStreams('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (DomainEvent $event, array $state, ReadModelScope $scope): array {
            if ($state['count'] === 1) {
                $scope->readModel()->stack('insert', $event->header(Header::EVENT_ID), $event->toContent());
            } else {
                $scope->readModel()->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);
            }

            $state['count']++;

            return $state;
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
        ->fromStreams('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (DomainEvent $event, array $state, ReadModelScope $scope): array {
            $state['count']++;

            if ($state['count'] === 1) {
                $scope->readModel()->stack('insert', $event->header(Header::EVENT_ID), $event->toContent());
            } else {
                $scope->readModel()->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);
            }

            return $state;
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
        ->fromStreams('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (DomainEvent $event, array $state, ReadModelScope $scope): array {
            if ($state['count'] === 1) {
                $scope->readModel()->stack('insert', $event->header(Header::EVENT_ID), $event->toContent());
            } else {
                $scope->readModel()->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);
            }

            $state['count']++;

            return $state;
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
        ->fromStreams('user')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (DomainEvent $event, array $state, ReadModelScope $scope): array {
            if ($state['count'] === 1) {
                $scope->readModel()->stack('insert', $event->header(Header::EVENT_ID), ['count' => $event->header(EventHeader::INTERNAL_POSITION)]);
            } else {
                $scope->readModel()->stack('update', $event->header(Header::EVENT_ID), 'count', $event->header(EventHeader::INTERNAL_POSITION));
            }

            $state['count']++;

            return $state;
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

test('can run read model projection from many streams', function () {
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
        ->initialize(fn () => ['count_some_event' => 0, 'count_another_event' => 0])
        ->fromStreams('debit', 'credit')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (DomainEvent $event, array $state, ReadModelScope $scope): array {
            if ($scope->streamName() === 'debit') {
                $state['count_some_event']++;

                if ($state['count_some_event'] === 1) {
                    $scope->readModel()->stack('insert', $event->header(Header::EVENT_ID), $event->toContent());
                } else {
                    $scope->readModel()->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);
                }
            } else {
                $state['count_another_event']++;

                $scope->readModel()->stack('increment', $event->header(Header::EVENT_ID), 'count', 1);
            }

            return $state;
        })->run(false);

    expect($projector->getState())
        ->toBe(['count_some_event' => 10, 'count_another_event' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 20]]);
});

test('can run read model projection from many streams and sort events by ascending order', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream1 = $this->testFactory->getStream('debit', 1, '-50 second', $eventId);
    $stream2 = $this->testFactory->getStream('debit', 1, '-30 second', $eventId);
    $this->eventStore->firstCommit($stream1);
    $this->eventStore->amend($stream2);

    $stream3 = $this->testFactory->getStream('credit', 1, '-40 seconds', $eventId, AnotherEvent::class);
    $stream4 = $this->testFactory->getStream('credit', 1, '-20 seconds', $eventId, AnotherEvent::class);
    $this->eventStore->firstCommit($stream3);
    $this->eventStore->amend($stream4);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('balance', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['order' => [], 'index' => 0])
        ->fromStreams('debit', 'credit')
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (DomainEvent $event, array $state, ReadModelScope $scope): array {
            $state['order'][$scope->streamName()][$state['index'] + 1] = $event::class;

            $state['index']++;

            return $state;
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
        ->fromStreams('debit')
        ->until(1)
        ->withQueryFilter($this->fromIncludedPosition)
        ->when(function (DomainEvent $event, array $state, ReadModelScope $scope): array {
            $state['count']++;

            if ($state['count'] === 1) {
                $scope->stack('insert', $event->header(Header::EVENT_ID), $event->toContent());
            } else {
                $scope->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);
            }

            return $state;
        })->run(true);

    expect($projector->getState())->toBe(['count' => 50]);
});

test('raise exception when query filter is not a projection query filter', function () {
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModelProjector('customer', $readModel);

    $projector
        ->fromStreams('user')
        ->withQueryFilter($this->createMock(QueryFilter::class))
        ->when(function (DomainEvent $event): void {
        })->run(false);
})->throws(RuntimeException::class, 'Persistent subscription requires a projection query filter');
