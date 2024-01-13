<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Feature\Projection;

use Chronhub\Storm\Contracts\Projector\EmitterScope;
use Chronhub\Storm\Projector\Checkpoint\GapType;
use Chronhub\Storm\Projector\Scope\EmitterAccess;
use Chronhub\Storm\Projector\Workflow\HaltOn;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Factory\InMemoryFactory;
use Chronhub\Storm\Tests\Stubs\Double\AnotherEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Symfony\Component\Uid\Uuid;

use function time;

beforeEach(function () {
    $this->testFactory = new InMemoryFactory();
    $this->eventStore = $this->testFactory->getEventStore();
    $this->projectorManager = $this->testFactory->getManager();
    $this->fromIncludedPosition = $this->projectorManager->queryScope()->fromIncludedPosition();
});

it('stop when no event stream has been discovered', function (): void {
    expect($this->eventStore->hasStream(new StreamName('user')))->toBeFalse();

    $haltOnEmpty = $this->projectorManager->newEmitterProjector('customer');

    $haltOnEmpty
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($this->fromIncludedPosition)
        ->haltOn(function (HaltOn $halt): HaltOn {
            return $halt->whenEmptyEventStream();
        })
        ->when(function (EmitterAccess $scope): void {
            //
        })
        ->run(true);

    expect($haltOnEmpty->getState())->toBe(['count' => 0]);
});

it('stop when no event stream discovered with expiration time', function (): void {
    expect($this->eventStore->hasStream(new StreamName('user')))->toBeFalse();

    $haltOnEmpty = $this->projectorManager->newEmitterProjector('customer');

    $currentTime = time();
    $expiredAt = $currentTime + 5;

    $haltOnEmpty
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($this->fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->whenEmptyEventStream($expiredAt))
        ->when(function (EmitterAccess $scope): void {
            //
        })
        ->run(true);

    expect($haltOnEmpty->getState())->toBe(['count' => 0])
        ->and(time() - $currentTime)->toBeGreaterThan(5);
});

it('stop when a recoverable gap is detected', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // induce gap
    $stream = $this->testFactory->getStream('user', 10, null, $eventId, SomeEvent::class, 12);
    $this->eventStore->amend($stream);

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    // halt on gap
    $haltOnGap = $this->projectorManager->newEmitterProjector('customer');

    $haltOnGap
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($this->fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->whenGapDetected(GapType::RECOVERABLE_GAP))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOnGap->getState())->toBe(['count' => 10]); // gap is still recoverable and not inserted in checkpoint
});

it('stop when a non recoverable gap is detected', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // induce gap
    $stream = $this->testFactory->getStream('user', 10, null, $eventId, SomeEvent::class, 12);
    $this->eventStore->amend($stream);

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    // halt on gap
    $haltOnGap = $this->projectorManager->newEmitterProjector('customer');

    $haltOnGap
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($this->fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->whenGapDetected(GapType::UNRECOVERABLE_GAP))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOnGap->getState())->toBe(['count' => 10]); // 10 is the last position before gap
});

it('stop when gap is detected', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // induce gap
    $stream = $this->testFactory->getStream('user', 10, null, $eventId, SomeEvent::class, 12);
    $this->eventStore->amend($stream);

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    // halt on gap
    $haltOnGap = $this->projectorManager->newEmitterProjector('customer');

    $haltOnGap
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($this->fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->whenGapDetected(GapType::IN_GAP))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOnGap->getState())->toBe(['count' => 11]); // 11 is the last position with gap
});

it('stop when counter is reached', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 50, null, $eventId);
    $this->eventStore->firstCommit($stream);

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    // run without halt on gap
    $keepEmitting = $this->projectorManager->newEmitterProjector('customer');

    $keepEmitting->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($this->fromIncludedPosition)
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(false);

    expect($keepEmitting->getState())->toBe(['count' => 50]);

    // delete projection
    $keepEmitting->delete(false);

    // stop at 22
    $haltOn = $this->projectorManager->newEmitterProjector('customer');

    $haltOn
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($this->fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->whenStreamEventLimitReach(22))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOn->getState())->toBe(['count' => 22]);
});

it('stop when time expires', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 5, null, $eventId);
    $this->eventStore->firstCommit($stream);

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    // run for 5 seconds
    $haltOn = $this->projectorManager->newEmitterProjector('customer');

    $start = time();
    $expiredAt = $start + 2;

    $haltOn
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($this->fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->whenTimeExpired($expiredAt))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOn->getState())->toBe(['count' => 5]);
});

it('stop after first cycle when expired time is zero', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 50, null, $eventId);
    $this->eventStore->firstCommit($stream);
    $fromIncludedPosition = $this->projectorManager->queryScope()->fromIncludedPosition();

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    $haltOn = $this->projectorManager->newEmitterProjector('customer');

    $haltOn
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->whenTimeExpired(0))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOn->getState())->toBe(['count' => 50]);
});

it('stop at cycle', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 50, null, $eventId);
    $this->eventStore->firstCommit($stream);
    $fromIncludedPosition = $this->projectorManager->queryScope()->fromIncludedPosition();

    expect($this->eventStore->hasStream(new StreamName('customer')))->toBeFalse();

    // stop at cycle 2
    $haltOn = $this->projectorManager->newEmitterProjector('customer');

    $haltOn
        ->initialize(fn () => ['count' => 0])
        ->subscribeToStream('user')
        ->filter($fromIncludedPosition)
        ->haltOn(fn (HaltOn $halt): HaltOn => $halt->whenCycleReach(2))
        ->when(function (EmitterAccess $scope): void {
            $scope->ack(SomeEvent::class)->incrementState();
        })
        ->run(true);

    expect($haltOn->getState())->toBe(['count' => 50]);
});

it('can run emitter again and reset main limit', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10000, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newEmitterProjector('customer');

    // run projection
    $projector
        ->initialize(fn () => ['count' => ['user' => 0, 'foo' => 0, 'total' => 0]])
        ->subscribeToStream('user', 'foo')
        ->haltOn(fn (HaltOn $haltOn): HaltOn => $haltOn->whenStreamEventLimitReach(10000, true))
        ->filter($this->fromIncludedPosition)
        ->when(function (EmitterScope $scope): void {
            $scope
                ->ackOneOf(SomeEvent::class, AnotherEvent::class)
                ->incrementState('count.'.$scope->streamName())
                ->incrementState('count.total');
        })->run(true);

    expect($projector->getState())->toBe(['count' => ['user' => 10000, 'foo' => 0, 'total' => 10000]]);

    $eventId1 = Uuid::v4()->toRfc4122();
    $stream1 = $this->testFactory->getStream('foo', 5000, '+10 seconds', $eventId1);
    $this->eventStore->firstCommit($stream1);

    $projector
        ->haltOn(fn (HaltOn $haltOn): HaltOn => $haltOn->whenStreamEventLimitReach(5000))
        ->run(true);

    expect($projector->getState())->toBe(['count' => ['user' => 10000, 'foo' => 5000, 'total' => 15000]]);
});

it('can run emitter again and do not reset main limit', function (): void {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10000, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $projector = $this->projectorManager->newEmitterProjector('customer');

    // run projection
    $projector
        ->initialize(fn () => ['count' => ['user' => 0, 'foo' => 0, 'total' => 0]])
        ->subscribeToStream('user', 'foo')
        ->haltOn(fn (HaltOn $haltOn): HaltOn => $haltOn->whenStreamEventLimitReach(10000, false))
        ->filter($this->fromIncludedPosition)
        ->when(function (EmitterScope $scope): void {
            $scope
                ->ackOneOf(SomeEvent::class, AnotherEvent::class)
                ->incrementState('count.'.$scope->streamName())
                ->incrementState('count.total');
        })->run(true);

    expect($projector->getState())->toBe(['count' => ['user' => 10000, 'foo' => 0, 'total' => 10000]]);

    $eventId1 = Uuid::v4()->toRfc4122();
    $stream1 = $this->testFactory->getStream('foo', 5000, '+10 seconds', $eventId1);
    $this->eventStore->firstCommit($stream1);

    $projector
        ->haltOn(fn (HaltOn $haltOn): HaltOn => $haltOn->whenStreamEventLimitReach(15000))
        ->run(true);

    expect($projector->getState())->toBe(['count' => ['user' => 10000, 'foo' => 5000, 'total' => 15000]]);
});
