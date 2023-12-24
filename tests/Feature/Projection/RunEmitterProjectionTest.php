<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Feature;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scope\EmitterAccess;
use Chronhub\Storm\Projector\Scope\QueryAccess;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Factory\InMemoryFactory;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $this->testFactory = new InMemoryFactory();
    $this->eventStore = $this->testFactory->getEventStore();
    $this->projectorManager = $this->testFactory->getManager();
});

it('can run emitter projection 111', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 2, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newEmitterProjector('customer');

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (EmitterAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->incrementState()
                ->when($scope['count'] === 1, function (EmitterAccess $scope): void {
                    expect($scope)
                        ->toBeInstanceOf(EmitterAccess::class)
                        ->and($scope->streamName())->toBe('user')
                        ->and($scope->clock())->toBeInstanceOf(PointInTime::class)
                        ->and($scope->event())->toBeInstanceOf(SomeEvent::class);
                });

            expect($scope->streamName())->not()->toBeNull();
        })->run(false);

    expect($projector->getState())->toBe(['count' => 2]);
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
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (EmitterAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->mergeState('events', [$scope->event()])
                ->emit($scope->event());
        })->run(false);

    expect($emitter->getState()['events'])->toHaveCount(10)
        ->and($this->eventStore->hasStream(new StreamName('customer')))->toBeTrue();

    // query from customer stream
    $query = $this->projectorManager->newQueryProjector();

    $query
        ->initialize(fn () => ['events' => []])
        ->subscribeToStream('customer')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->mergeState('events', $scope->event());
        })->run(false);

    expect($query->getState()['events'])->toHaveCount(10)
        ->and($query->getState()['events'])->toBe($emitter->getState()['events']);
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
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (EmitterAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->when(
                    $scope->event()->header(EventHeader::INTERNAL_POSITION) % 2 === 0,
                    function (EmitterAccess $scope): void {
                        $scope
                            ->mergeState('even', $scope->event())
                            ->linkTo('user_even', $scope->event());
                    }, function (EmitterAccess $scope): void {
                        $scope
                            ->mergeState('odd', $scope->event())
                            ->linkTo('user_odd', $scope->event());
                    }
                );
        })->run(false);

    expect($emitter->getState()['odd'])->toHaveCount(5)
        ->and($emitter->getState()['even'])->toHaveCount(5)
        ->and($this->eventStore->hasStream(new StreamName('user_odd')))->toBeTrue()
        ->and($this->eventStore->hasStream(new StreamName('user_even')))->toBeTrue();

    // query from odd/even user streams
    $query = $this->projectorManager->newQueryProjector();

    $query
        ->initialize(fn () => ['odd' => [], 'even' => []])
        ->subscribeToStream('user_odd', 'user_even')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->when(
                    $scope->streamName() === 'user_odd',
                    function (QueryAccess $scope): void {
                        $scope->mergeState('odd', $scope->event());
                    }, function (QueryAccess $scope): void {
                        $scope->mergeState('even', $scope->event());
                    }
                );
        })->run(false);

    expect($query->getState())->toBe($emitter->getState());
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
        ->subscribeToStream('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (EmitterAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ->when(
                    $scope->event()->header(EventHeader::INTERNAL_POSITION) % 2 === 0,
                    function (EmitterAccess $scope): void {
                        $scope
                            ->mergeState('even', $scope->event())
                            ->linkTo('customer-even', $scope->event());
                    }, function (EmitterAccess $scope): void {
                        $scope
                            ->mergeState('odd', $scope->event())
                            ->linkTo('customer-odd', $scope->event());
                    }
                );
        })->run(false);

    expect($emitter->getState()['odd'])->toHaveCount(5)
        ->and($emitter->getState()['even'])->toHaveCount(5)
        ->and($this->eventStore->hasStream(new StreamName('customer-odd')))->toBeTrue()
        ->and($this->eventStore->hasStream(new StreamName('customer-even')))->toBeTrue();

    // query from odd/even customer categories
    $query = $this->projectorManager->newQueryProjector();

    $query
        ->initialize(fn () => ['odd' => [], 'even' => []])
        ->subscribeToCategory('customer')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (QueryAccess $scope): void {
            $scope
                ->ack(SomeEvent::class)
                ?->when(
                    $scope->streamName() === 'customer-odd',
                    function (QueryAccess $scope): void {
                        $scope->mergeState('odd', $scope->event());
                    }, function (QueryAccess $scope): void {
                        $scope->mergeState('even', $scope->event());
                    }
                );
        })->run(false);

    expect($query->getState())->toBe($emitter->getState());
});

it('raise exception when query filter is not a projection query filter', function () {
    $projector = $this->projectorManager->newEmitterProjector('customer');

    $projector
        ->subscribeToStream('user')
        ->withQueryFilter($this->createMock(QueryFilter::class))
        ->when(fn () => null)
        ->run(false);
})->throws(RuntimeException::class, 'Persistent subscription requires a projection query filter');
