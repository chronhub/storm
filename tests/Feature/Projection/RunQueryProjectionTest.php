<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Feature;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Clock\PointInTimeFactory;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Filter\InMemoryLimitByOneQuery;
use Chronhub\Storm\Projector\Scope\QueryAccess;
use Chronhub\Storm\Projector\Workflow\HaltOn;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Factory\InMemoryFactory;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $this->testFactory = new InMemoryFactory();
    $this->eventStore = $this->testFactory->getEventStore();
    $this->projectorManager = $this->testFactory->getManager();
});

it('can run query projection 1', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 100, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->incrementState()
                ->when($scope['count'] === 1, function ($scope) {
                    expect($scope)->toBeInstanceOf(QueryAccess::class)
                        ->and($scope->streamName())->toBe('user')
                        ->and($scope->clock())->toBeInstanceOf(PointInTime::class);
                });
        })->run(false);

    expect($projector->getState())->toBe(['count' => 100]);
});

it('can run query projection until and increment loop', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 5, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // force to only handle one event
    $queryFilter = new InMemoryLimitByOneQuery();

    $expiredAt = PointInTimeFactory::now()->modify('+1 seconds')->getTimestamp();
    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($queryFilter)
        ->when(function (QueryAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->haltOn(fn (HaltOn $haltOn): HaltOn => $haltOn->expiredAt($expiredAt))
        ->run(true);

    expect($projector->getState())->toBe(['count' => 5]);
})->group('sleep');

it('can stop query projection', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->incrementState()
                ->stopWhen($scope['count'] === 5);
        })->run(false);
    expect($projector->getState())->toBe(['count' => 5]);
});

it('can run query projection in background with timer 1000', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->until(1)
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })->run(true);

    expect($projector->getState())->toBe(['count' => 10]);
});

it('rerun a completed query projection will return the original initialized state', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })->run(false);

    expect($projector->getState())->toBe(['count' => 10]);

    $projector->run(false);

    expect($projector->getState())->toBe(['count' => 0]);
});

it('can rerun query projection from incomplete run and override state', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 5, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // first run
    $projector
        ->initialize(fn () => ['count' => 0, 'ids' => []])
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->incrementState()
                ->mergeState('ids', $scope->event()->header(EventHeader::INTERNAL_POSITION));
        })->run(false);

    expect($projector->getState())->toBe(['count' => 5, 'ids' => [1, 2, 3, 4, 5]]);

    // append new events
    $appends = $this->testFactory->getStream('user', 3, '+10 seconds', $eventId, SomeEvent::class, 6);
    $this->eventStore->amend($appends);

    // second run
    $projector->run(false);
    expect($projector->getState())->toBe(['count' => 3, 'ids' => [6, 7, 8]]);

    // third run for a "completed" state
    $projector->run(false);
    expect($projector->getState())->toBe(['count' => 0, 'ids' => []]);
});

it('assert query projection does not handle gap', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 1, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    $stream1 = $this->testFactory->getStream('user', 1, '+1 seconds', $eventId, SomeEvent::class, 5);
    $this->eventStore->amend($stream1);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run once
    $projector
        ->initialize(fn () => ['count' => 0, 'ids' => []])
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->incrementState()
                ->mergeState('ids', $scope->event()->header(EventHeader::INTERNAL_POSITION));
        })->run(false);

    expect($projector->getState())->toBe(['count' => 2, 'ids' => [1, 5]]);
});

it('can rerun query projection while keeping state in memory', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->withKeepState()
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })->run(false);

    expect($projector->getState())->toBe(['count' => 10]);

    $appends = $this->testFactory->getStream('user', 20, '+10 seconds', $eventId, SomeEvent::class, 11);
    $this->eventStore->amend($appends);

    $projector->run(false);

    expect($projector->getState())->toBe(['count' => 30]);
});

it('raise exception when keeping state in memory but user state has not been initialized', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 1, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->withKeepState()
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (): void {
            throw new RuntimeException('Should not be called');
        })->run(false);

    expect($projector->getState())->toBe([]);

    $projector->run(false);
})->throws(RuntimeException::class, 'Projection context is not initialized');

it('can reset query projection and re run from scratch', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })->run(false);

    expect($projector->getState())->toBe(['count' => 10]);

    $projector->reset();

    $projector->run(false);

    expect($projector->getState())->toBe(['count' => 10]);
});

it('can run query projection from category', function () {
    // feed our event store
    $eventId1 = Uuid::v4()->toRfc4122();
    $credit = $this->testFactory->getStream('balance-credit', 2, '+1 seconds', $eventId1);
    $this->eventStore->firstCommit($credit);

    $eventId2 = Uuid::v4()->toRfc4122();
    $debit = $this->testFactory->getStream('balance-debit', 2, '+1 seconds', $eventId2);
    $this->eventStore->firstCommit($debit);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToCategory('balance')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })->run(false);

    expect($projector->getState())->toBe(['count' => 4]);
});

it('can run query projection from all streams', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, '-50 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    $eventId1 = Uuid::v4()->toRfc4122();
    $credit = $this->testFactory->getStream('balance-credit', 2, '-20 seconds', $eventId1);
    $this->eventStore->firstCommit($credit);

    $eventId2 = Uuid::v4()->toRfc4122();
    $debit = $this->testFactory->getStream('balance-debit', 2, '+10 seconds', $eventId2);
    $this->eventStore->firstCommit($debit);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToAll()
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })->run(false);

    expect($projector->getState())->toBe(['count' => 14]);
});

it('can run query projection from current stream position', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 5, null, $eventId);
    $this->eventStore->firstCommit($stream);

    $queryFilter = new InMemoryLimitByOneQuery();

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => ['position' => []])
        ->subscribeToAll()
        ->withQueryFilter($queryFilter)
        ->when(function (QueryAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->mergeState('position', [$scope->event()->header(EventHeader::INTERNAL_POSITION)]);
        })->run(false);

    expect($projector->getState())->toBe(['position' => [1]]);

    $current = 1;
    $next = 4;
    while ($next > 0) {
        $projector->run(false);
        expect($projector->getState())->toBe(['position' => [$current + 1]]);
        $next--;
        $current++;
    }

    expect($projector->getState())->toBe(['position' => [5]]);
});

it('can run query projection with a dedicated query filter', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    $queryFilter = new class() implements InMemoryQueryFilter
    {
        public function apply(): callable
        {
            return fn (DomainEvent $event): bool => (int) $event->header(EventHeader::INTERNAL_POSITION) > 7;
        }

        public function orderBy(): string
        {
            return 'asc';
        }
    };

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => ['positions' => []])
        ->subscribeToAll()
        ->withQueryFilter($queryFilter)
        ->when(function (QueryAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->mergeState('positions', $scope->event()->header(EventHeader::INTERNAL_POSITION));
        })->run(false);

    expect($projector->getState())->toBe(['positions' => [8, 9, 10]]);
});

it('can run query projection with user state 123', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 1, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            expect($scope->getState())->toBeArray()->and($scope['count'])->toBe(0);
        })->run(false);

    expect($projector->getState())->toBe(['count' => 0]);
});

it('can run query projection with empty user state', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 1, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->initialize(fn () => [])
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            expect($scope->getState())->toBeArray()->toBeEmpty();

            $scope['count'] = 1;
        })->run(false);

    expect($projector->getState())->toBe(['count' => 1]);
});

it('can run query projection without user state', function () {
    // unless, dev uses his own state, a query projection is useless without an initial state

    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 1, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQueryProjector();

    // run projection
    $projector
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            expect($scope->getState())->toBeNull();
        })->run(false);

    expect($projector->getState())->toBe([]);
});
