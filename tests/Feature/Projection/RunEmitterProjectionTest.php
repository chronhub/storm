<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Feature;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scheme\EmitterAccess;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Factory\InMemoryFactory;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $this->testFactory = new InMemoryFactory();
    $this->eventStore = $this->testFactory->getEventStore();
    $this->projectorManager = $this->testFactory->getManager();
});

it('can run emitter projection', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newEmitterProjector('customer');

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state, EmitterScope $scope): array {
            if ($state['count'] === 1) {
                expect($scope)
                    ->toBeInstanceOf(EmitterAccess::class)
                    ->and($scope->streamName())->toBe('user')
                    ->and($scope->clock())->toBeInstanceOf(PointInTime::class)
                    ->and($event)->toBeInstanceOf(SomeEvent::class);
            }

            expect($scope->streamName())->not()->toBeNull();

            $state['count']++;

            return $state;
        })->run(false);

    expect($projector->outputState())->toBe(['count' => 10]);
});

it('can emit event to a new stream named from projection', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    // create a projection
    $emitter = $this->projectorManager->newEmitterProjector('customer');

    // run projection
    $emitter
        ->initialize(fn () => ['events' => []])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state, EmitterAccess $scope): array {
            $scope->emit($event);

            $state['events'][] = $event;

            return $state;
        })->run(false);

    expect($emitter->outputState()['events'])->toHaveCount(10)
        ->and($this->eventStore->hasStream(new StreamName('customer')))->toBeTrue();

    // query from customer stream
    $query = $this->projectorManager->newQueryProjector();

    $query
        ->initialize(fn () => ['events' => []])
        ->fromStreams('customer')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            $state['events'][] = $event;

            return $state;
        })->run(false);

    expect($query->outputState()['events'])->toHaveCount(10)
        ->and($query->outputState()['events'])->toBe($emitter->outputState()['events']);
});

it('can link event to a new stream', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    expect($this->eventStore->hasStream(new StreamName('user_odd')))->toBeFalse()
        ->and($this->eventStore->hasStream(new StreamName('user_even')))->toBeFalse();

    // create a projection
    $emitter = $this->projectorManager->newEmitterProjector('customer');

    // run projection
    $emitter
        ->initialize(fn () => ['odd' => [], 'even' => []])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state, EmitterScope $scope): array {
            $eventType = ($event->header(EventHeader::INTERNAL_POSITION) % 2 === 0) ? 'even' : 'odd';

            $scope->linkTo('user_'.$eventType, $event);
            $state[$eventType][] = $event;

            return $state;
        })->run(false);

    expect($emitter->outputState()['odd'])->toHaveCount(5)
        ->and($emitter->outputState()['even'])->toHaveCount(5)
        ->and($this->eventStore->hasStream(new StreamName('user_odd')))->toBeTrue()
        ->and($this->eventStore->hasStream(new StreamName('user_even')))->toBeTrue();

    // query from odd/even user streams
    $query = $this->projectorManager->newQueryProjector();

    $query
        ->initialize(fn () => ['odd' => [], 'even' => []])
        ->fromStreams('user_odd', 'user_even')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state, QueryProjectorScope $projector): array {
            $projector->streamName() === 'user_odd'
                ? $state['odd'][] = $event
                : $state['even'][] = $event;

            return $state;
        })->run(false);

    expect($query->outputState())->toBe($emitter->outputState());
});

it('can link event to new categories', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    expect($this->eventStore->hasStream(new StreamName('customer-odd')))->toBeFalse()
        ->and($this->eventStore->hasStream(new StreamName('customer-even')))->toBeFalse();

    // create a projection
    $emitter = $this->projectorManager->newEmitterProjector('customer');
    // run projection
    $emitter
        ->initialize(fn () => ['odd' => [], 'even' => []])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state, EmitterScope $projector): array {
            $eventType = ($event->header(EventHeader::INTERNAL_POSITION) % 2 === 0) ? 'even' : 'odd';

            $projector->linkTo('customer-'.$eventType, $event);

            $state[$eventType][] = $event;

            return $state;
        })->run(false);

    expect($emitter->outputState()['odd'])->toHaveCount(5)
        ->and($emitter->outputState()['even'])->toHaveCount(5)
        ->and($this->eventStore->hasStream(new StreamName('customer-odd')))->toBeTrue()
        ->and($this->eventStore->hasStream(new StreamName('customer-even')))->toBeTrue();

    // query from odd/even customer categories
    $query = $this->projectorManager->newQueryProjector();

    $query
        ->initialize(fn () => ['odd' => [], 'even' => []])
        ->fromCategories('customer')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state, QueryProjectorScope $scope): array {
            $scope->streamName() === 'customer-odd'
                ? $state['odd'][] = $event
                : $state['even'][] = $event;

            return $state;
        })->run(false);

    expect($query->outputState())->toBe($emitter->outputState());
});

it('raise exception when query filter is not a projection query filter', function () {
    $projector = $this->projectorManager->newEmitterProjector('customer');

    $projector
        ->fromStreams('user')
        ->withQueryFilter($this->createMock(QueryFilter::class))
        ->when(function (DomainEvent $event): void {
        })->run(false);
})->throws(RuntimeException::class, 'Persistent subscription requires a projection query filter');
