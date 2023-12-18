<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Uses;

use Chronhub\Storm\Contracts\Projector\ProjectionRepository;
use Chronhub\Storm\Projector\Repository\EventMap;
use Chronhub\Storm\Projector\Repository\Events\ProjectionError;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Throwable;

trait TestingEventDispatcherRepository
{
    protected string $streamName = 'customer';

    protected Dispatcher $eventDispatcher;

    protected ProjectionRepository|MockObject $repository;

    protected object $eventSubscriber;

    protected function assertEventDispatched(string $event, callable $callback): void
    {
        $this->setEventSubscriber();

        expect($this->eventSubscriber->getListeners())->toContain($event);

        $callback($this);

        $eventsHandled = $this->eventSubscriber->eventsHandled();

        expect($eventsHandled)->toHaveCount(1);

        $eventHandled = $eventsHandled[0];

        expect($eventHandled::class)
            ->toBe($event)
            ->and($eventHandled->streamName)->toBe($this->streamName);
    }

    protected function assertErrorEventDispatched(string $event, callable $callback, Throwable $exception): void
    {
        $this->setEventSubscriber();

        expect($this->eventSubscriber->getListeners())->toContain($event);

        $exceptionRaised = null;

        try {
            $callback($this);
        } catch (Throwable $e) {
            $exceptionRaised = $e;
        }

        expect($exceptionRaised)->toBe($exception);

        $eventsHandled = $this->eventSubscriber->eventsHandled();

        expect($eventsHandled)->toHaveCount(1);

        $eventHandled = $eventsHandled[0];

        expect($eventHandled::class)->toBe(ProjectionError::class)
            ->and($eventHandled->error)->toBe($exception)
            ->and($eventHandled->event)->toBe($event)
            ->and($eventHandled->streamName)->toBe($this->streamName);
    }

    protected function assertNoEventDispatched(callable $callback): void
    {
        $this->setEventSubscriber();

        $callback();

        expect($this->eventSubscriber->eventsHandled())->toBeEmpty();
    }

    protected function setEventSubscriber(): void
    {
        $this->eventSubscriber = new class()
        {
            private EventMap $eventMap;

            private array $eventsHandled = [];

            public function __construct()
            {
                $this->eventMap = new EventMap();
            }

            public function handle(object $event): void
            {
                $this->eventsHandled[] = $event;
            }

            public function eventsHandled(): array
            {
                return $this->eventsHandled;
            }

            public function subscribe(Dispatcher $dispatcher): void
            {
                foreach ($this->eventMap->events() as $event) {
                    $dispatcher->listen($event, [$this, 'handle']);
                }
            }

            /**
             * @return array<class-string>
             */
            public function getListeners(): array
            {
                return $this->eventMap->events();
            }
        };

        $this->eventSubscriber->subscribe($this->eventDispatcher);
    }
}
