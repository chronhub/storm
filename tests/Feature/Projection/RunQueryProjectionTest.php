<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Feature;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScopeInterface;
use Chronhub\Storm\Projector\Scheme\QueryProjectorScope;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Factory\InMemoryFactory;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $this->testFactory = new InMemoryFactory();
    $this->eventStore = $this->testFactory->getEventStore();
    $this->projectorManager = $this->testFactory->getManager();
});

it('can run query projection', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state, QueryProjectorScopeInterface $scope): array {
            $state['count']++;

            if ($state['count'] === 1) {
                expect($scope)->toBeInstanceOf(QueryProjectorScope::class)
                    ->and($scope->streamName())->toBe('user')
                    ->and($scope->clock())->toBeInstanceOf(PointInTime::class);
            }

            return $state;
        })->run(false);

    expect($projector->getState())->toBe(['count' => 10]);
});

it('can stop query projection', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state, QueryProjectorScopeInterface $scope): array {
            $state['count']++;

            if ($state['count'] === 5) {
                $scope->stop();
            }

            return $state;
        })->run(false);

    expect($projector->getState())->toBe(['count' => 5]);
});

it('can run query projection in background with timer', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromStreams('user')
        ->until(0)
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            $state['count']++;

            return $state;
        })->run(true);

    expect($projector->getState())->toBe(['count' => 10]);
});

it('rerun a completed query projection will return the original initialized state', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 5, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            $state['count']++;

            return $state;
        })->run(false);

    expect($projector->getState())->toBe(['count' => 5]);

    $projector->run(false);

    expect($projector->getState())->toBe(['count' => 0]);
});

it('can rerun query projection from incomplete run and override state', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 5, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQuery();

    // first run
    $projector
        ->initialize(fn () => ['count' => 0, 'ids' => []])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            $state['count']++;
            $state['ids'][] = $event->header(EventHeader::INTERNAL_POSITION);

            return $state;
        })->run(false);

    expect($projector->getState())->toBe(['count' => 5, 'ids' => [1, 2, 3, 4, 5]]);

    // append new events
    $appends = $this->testFactory->getStream('user', 3, '+10 seconds', $eventId, SomeEvent::class, 6);
    $this->eventStore->amend($appends);

    // second run
    $projector->run(false);
    expect($projector->getState())->toBe(['count' => 3, 'ids' => [6, 7, 8]]);

    // third run from "complete" state
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
    $projector = $this->projectorManager->newQuery();

    // run once
    $projector
        ->initialize(fn () => ['count' => 0, 'ids' => []])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            $state['count']++;
            $state['ids'][] = $event->header(EventHeader::INTERNAL_POSITION);

            return $state;
        })->run(false);

    expect($projector->getState())->toBe(['count' => 2, 'ids' => [1, 5]]);
});

it('can rerun query projection and catch up', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            $state['count']++;

            return $state;
        })->run(false);

    expect($projector->getState())->toBe(['count' => 10]);

    $appends = $this->testFactory->getStream('user', 20, '+10 seconds', $eventId, SomeEvent::class, 11);
    $this->eventStore->amend($appends);

    $projector->run(false);

    expect($projector->getState())->toBe(['count' => 30]);
})->todo();

it('can reset query projection and re run from scratch', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            $state['count']++;

            return $state;
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
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromCategories('balance')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            $state['count']++;

            return $state;
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
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromAll()
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            $state['count']++;

            return $state;
        })->run(false);

    expect($projector->getState())->toBe(['count' => 14]);
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
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => ['positions' => []])
        ->fromAll()
        ->withQueryFilter($queryFilter)
        ->when(function (DomainEvent $event, array $state): array {
            $state['positions'][] = $event->header(EventHeader::INTERNAL_POSITION);

            return $state;
        })->run(false);

    expect($projector->getState())->toBe(['positions' => [8, 9, 10]]);
});

it('can run query projection with user state', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 1, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, $state): array {
            expect($state)->toBeArray()->and($state['count'])->toBe(0);

            $state['count']++;

            return $state;
        })->run(false);

    expect($projector->getState())->toBe(['count' => 1]);
});

it('can run query projection with empty user state', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 1, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => [])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, $state): array {
            expect($state)->toBeArray()->toBeEmpty();

            return ['count' => 1];
        })->run(false);

    expect($projector->getState())->toBe(['count' => 1]);
});

it('can run query projection with user state and return', function (array $returnState) {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 1, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, $state) use ($returnState): array {
            expect($state)->toBeArray()->and($state['count'])->toBe(0);

            $state['count']++;

            return $returnState;
        })->run(false);

    expect($projector->getState())->toBe($returnState);
})->with([
    'whatever data' => [['return whatever data']],
    'empty array' => [[]],
]);

it('can run query projection without user state and return will be ignored', function () {
    // unless, dev uses his own state, a query projection is useless without state

    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 1, '+1 seconds', $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newQuery();

    // run projection
    $projector
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, ProjectorScope $scope, array $state = null): array {
            expect($state)->toBeNull();

            return ['foo' => 'bar'];
        })->run(false);

    expect($projector->getState())->toBe([]);
});

it('test state is not altered with no event to handle', function () {
    // create a projection
    $projector = $this->projectorManager->newQuery();

    $outState = ['foo' => 'bar'];

    // run projection
    $projector
        ->initialize(fn () => [])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state) use ($outState): array {
            return $outState;
        })->run(false);

    expect($projector->getState())->toBe([]);
});
