<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Repository\Event\ProjectionError;
use Chronhub\Storm\Projector\Repository\Event\ProjectionEventMap;
use Chronhub\Storm\Projector\Repository\Event\ProjectionStarted;
use Chronhub\Storm\Projector\Repository\EventDispatcherRepository;
use Chronhub\Storm\Tests\UnitTestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\Dispatcher as EventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Throwable;

class EventDispatcherProjectionRepositoryTest extends UnitTestCase
{
    private ProjectionRepositoryInterface|MockObject $repository;

    private Dispatcher $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ProjectionRepositoryInterface::class);
        $this->repository->expects($this->atLeast(1))->method('projectionName')->willReturn('foo');
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * @test
     */
    public function testStart(): void
    {
        $subscriber = $this->newSubscriber();
        $repository = $this->newEventAwareRepository();

        $repository->start();

        $this->assertInstanceOf(ProjectionStarted::class, $subscriber->eventHandled());
        $this->assertSame('foo', $subscriber->eventHandled()->streamName);
    }

    public function testStartOnError(): void
    {
        $subscriber = $this->newSubscriber();
        $exception = new RuntimeException('error');
        $this->repository->expects($this->once())->method('start')->willThrowException($exception);
        $repository = $this->newEventAwareRepository();

        try {
            $repository->start();
        } catch (Throwable $e) {
            $this->assertEquals($exception, $e);
        }

        $this->assertInstanceOf(ProjectionError::class, $subscriber->eventHandled());
        $this->assertSame($exception, $subscriber->eventHandled()->error);
        $this->assertSame('foo', $subscriber->eventHandled()->streamName);
    }

    private function newSubscriber(): object
    {
        $subscriber = new class()
        {
            private ProjectionEventMap $eventMap;

            private ?object $eventHandled = null;

            public function __construct()
            {
                $this->eventMap = new ProjectionEventMap();
            }

            public function handle(object $event): void
            {
                $this->eventHandled = $event;
            }

            public function eventHandled(): ?object
            {
                return $this->eventHandled;
            }

            public function subscribe(Dispatcher $dispatcher): void
            {
                foreach ($this->eventMap->events() as $event) {
                    $dispatcher->listen($event, [$this, 'handle']);
                }
            }
        };

        $subscriber->subscribe($this->eventDispatcher);

        return $subscriber;
    }

    private function newEventAwareRepository(): EventDispatcherRepository
    {
        return new EventDispatcherRepository($this->repository, $this->eventDispatcher);
    }
}
