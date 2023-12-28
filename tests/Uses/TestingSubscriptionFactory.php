<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Uses;

use Chronhub\Storm\Contracts\Projector\CheckpointRecognition;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriber;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\StateManagement;
use Chronhub\Storm\Projector\Support\EventCounter;
use Chronhub\Storm\Projector\Workflow\InMemoryUserState;
use Chronhub\Storm\Projector\Workflow\Sprint;
use PHPUnit\Framework\MockObject\MockObject;
use tests\TestCase;

trait TestingSubscriptionFactory
{
    protected StateManagement&MockObject $subscription;

    protected ProjectorScope&MockObject $projectorScope;

    protected ProjectionOption&MockObject $option;

    protected CheckpointRecognition&MockObject $streamManager;

    protected ContextReader&MockObject $context;

    protected InMemoryUserState $state;

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
        $this->streamManager = $this->createMock(CheckpointRecognition::class);
        $this->context = $this->createMock(ContextReader::class);
        $this->state = new InMemoryUserState(); // mock
        $this->sprint = new Sprint();

        $this->subscription->method('option')->willReturn($this->option);
        $this->subscription->method('streamManager')->willReturn($this->streamManager);
        $this->subscription->method('context')->willReturn($this->context);
        $this->subscription->method('state')->willReturn($this->state);
        $this->subscription->method('sprint')->willReturn($this->sprint);

        // should be defined in test
        $this->subscription->method('currentStreamName')->willReturn($this->currentStreamName);

        if ($this->subscription instanceof PersistentSubscriber) {
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
