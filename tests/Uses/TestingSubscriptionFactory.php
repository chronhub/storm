<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Uses;

use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\Sprint;
use PHPUnit\Framework\MockObject\MockObject;
use tests\TestCase;

trait TestingSubscriptionFactory
{
    protected Subscription&MockObject $subscription;

    protected ProjectorScope&MockObject $projectorScope;

    protected ProjectionOption&MockObject $option;

    protected StreamManagerInterface&MockObject $streamManager;

    protected ContextReaderInterface&MockObject $context;

    protected ProjectionState $state;

    protected Sprint $sprint;

    protected ?EventCounter $eventCounter = null;

    protected string $currentStreamName = 'foo';

    protected int $eventCounterLimit = 1;

    protected function setUpWithSubscription(string $subscription, string $projectorScope): void
    {
        /** @var TestCase $this */
        $this->subscription = $this->createMock($subscription);
        $this->projectorScope = $this->createMock($projectorScope);

        $this->option = $this->createMock(ProjectionOption::class);
        $this->streamManager = $this->createMock(StreamManagerInterface::class);
        $this->context = $this->createMock(ContextReaderInterface::class);
        $this->state = new ProjectionState(); // mock
        $this->sprint = new Sprint();

        $this->subscription->method('option')->willReturn($this->option);
        $this->subscription->method('streamManager')->willReturn($this->streamManager);
        $this->subscription->method('context')->willReturn($this->context);
        $this->subscription->method('state')->willReturn($this->state);
        $this->subscription->method('sprint')->willReturn($this->sprint);

        // should be defined in test
        $this->subscription->method('currentStreamName')->willReturn($this->currentStreamName);

        if ($this->subscription instanceof PersistentSubscriptionInterface) {
            $this->eventCounter = new EventCounter($this->eventCounterLimit);

            $this->subscription->method('eventCounter')->willReturn($this->eventCounter);
        }
    }

    protected function fakeInitializeUserState(): void
    {
        // for event processor
        $this->context->method('userState')->willReturn(fn (): array => []);
    }
}
