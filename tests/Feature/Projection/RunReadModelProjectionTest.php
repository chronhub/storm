<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Feature;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Factory\InMemoryFactory;
use Chronhub\Storm\Tests\Stubs\Double\AnotherEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Symfony\Component\Uid\Uuid;

beforeEach(function () {
    $this->testFactory = new InMemoryFactory();
    $this->eventStore = $this->testFactory->getEventStore();
    $this->projectorManager = $this->testFactory->getManager();
});

it('can run read model projection', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModel('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state) use ($readModel): array {
            /** @var ReadModelProjectorScopeInterface $this */
            /** @phpstan-ignore-next-line  */
            if ($state['count'] === 1) {
                expect($this)
                    ->toBeInstanceOf(ReadModelProjectorScopeInterface::class)
                    ->and($this->streamName())->toBe('user')
                    ->and($this->clock())->toBeInstanceOf(PointInTime::class)
                    ->and($this->readModel())->toBe($readModel)
                    ->and($event)->toBeInstanceOf(SomeEvent::class);

                $this->readModel()->stack('insert', $event->header(Header::EVENT_ID), $event->toContent());
            } else {
                $this->readModel()->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);
            }

            expect($this->streamName())->not()->toBeNull();

            $state['count']++;

            return $state;
        })->run(false);

    expect($projector->getState())
        ->toBe(['count' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 10]]);
});

it('can stop read model projection', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModel('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count' => 0])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            /** @var ReadModelProjectorScopeInterface $this */
            /** @phpstan-ignore-next-line  */
            if ($state['count'] === 1) {
                $this->readModel()->stack('insert', $event->header(Header::EVENT_ID), $event->toContent());
            } else {
                $this->readModel()->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);
            }

            $state['count']++;

            if ($state['count'] === 7) {
                $this->stop();
            }

            return $state;
        })->run(false);

    expect($projector->getState())
        ->toBe(['count' => 7])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 7]]);
});

it('can run read model projection from many streams', function () {
    // feed our event store
    // fake data where debit event time are all less than credit event time
    $eventId = Uuid::v4()->toRfc4122();
    $stream1 = $this->testFactory->getStream('debit', 10, '-10 second', $eventId);
    $this->eventStore->firstCommit($stream1);

    $factory = InMemoryFactory::withEventStoreType(AnotherEvent::class);
    $stream2 = $factory->getStream('credit', 10, '+ 50 seconds', $eventId, AnotherEvent::class);
    $this->eventStore->firstCommit($stream2);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModel('balance', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['count_some_event' => 0, 'count_another_event' => 0])
        ->fromStreams('debit', 'credit')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            /** @var ReadModelProjectorScopeInterface $this */
            /** @phpstan-ignore-next-line */
            if ($this->streamName() === 'debit') {
                $state['count_some_event']++;

                if ($state['count_some_event'] === 1) {
                    $this->readModel()->stack('insert', $event->header(Header::EVENT_ID), $event->toContent());
                } else {
                    $this->readModel()->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);
                }
            } else {
                $state['count_another_event']++;

                $this->readModel()->stack('increment', $event->header(Header::EVENT_ID), 'count', 1);
            }

            return $state;
        })->run(false);

    expect($projector->getState())
        ->toBe(['count_some_event' => 10, 'count_another_event' => 10])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 20]]);
});

it('can run read model projection from many streams and sort events by ascending order', function () {
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
    $projector = $this->projectorManager->newReadModel('balance', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['order' => [], 'count' => 0])
        ->fromStreams('debit', 'credit')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
        ->when(function (DomainEvent $event, array $state): array {
            $state['order'][$this->streamName()][$state['count'] + 1] = $event::class;

            $state['count']++;

            return $state;
        })->run(false);

    expect($projector->getState()['order'])->toBe([
        'debit' => [1 => SomeEvent::class, 3 => SomeEvent::class],
        'credit' => [2 => AnotherEvent::class, 4 => AnotherEvent::class],
    ]);
});

it('can run read model projection with limit in query filter', function () {
    // feed our event store
    $eventId = Uuid::v4()->toRfc4122();
    $stream = $this->testFactory->getStream('user', 10, null, $eventId);
    $this->eventStore->firstCommit($stream);

    // create a projection
    $readModel = $this->testFactory->readModel;
    $projector = $this->projectorManager->newReadModel('customer', $readModel);

    // run projection
    $projector
        ->initialize(fn () => ['positions' => []])
        ->fromStreams('user')
        ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition(5))
        ->when(function (DomainEvent $event, array $state): array {
            if ($state['positions'] === []) {
                $this->readModel()->stack('insert', $event->header(Header::EVENT_ID), $event->toContent());
            } else {
                $this->readModel()->stack('update', $event->header(Header::EVENT_ID), 'count', $event->toContent()['count']);
            }

            $state['positions'][] = $event->header(EventHeader::INTERNAL_POSITION);

            return $state;
        })->run(false);

    expect($projector->getState())->toBe(['positions' => [1, 2, 3, 4, 5]])
        ->and($readModel->getContainer())->toBe([$eventId => ['count' => 5]]);
});
