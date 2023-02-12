<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;
use function posix_kill;
use function pcntl_signal;
use function posix_getpid;

final class PersistentEventProcessorTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy;

    /**
     * @test
     */
    public function it_test_preprocess_event_with_signal_dispatch(): void
    {
        $processEvent = new class extends EventProcessor
        {
            public function __invoke(Context $context, DomainEvent $event, int $key, ?ProjectorRepository $repository)
            {
                return $this->preProcess($context, $event, $key, $repository);
            }
        };

        $result = null;

        pcntl_signal(SIGHUP, function () use (&$result) {
            $result = 'signal handler dispatched';
        });

        posix_kill(posix_getpid(), SIGHUP);

        $context = $this->newContext();
        $context->currentStreamName = 'customer';

        $event = SomeEvent::fromContent([])->withHeader(Header::EVENT_TIME, 'some_datetime');

        $this->option->getDispatchSignal()->willReturn(true)->shouldBeCalledOnce();
        $this->gap->detect('customer', 14, 'some_datetime')->willReturn(false)->shouldBeCalledOnce();
        $this->position->bind('customer', 14)->shouldBeCalledOnce();
        $this->counter->increment()->shouldBeCalledOnce();

        $this->assertTrue($processEvent($context, $event, 14, $this->repository->reveal()));
        $this->assertEquals('signal handler dispatched', $result);
    }

    /**
     * @test
     */
    public function it_test_preprocess_event_for_persistent_projection_with_no_gap_detected(): void
    {
        $processEvent = new class extends EventProcessor
        {
            public function __invoke(Context $context, DomainEvent $event, int $key, ?ProjectorRepository $repository)
            {
                return $this->preProcess($context, $event, $key, $repository);
            }
        };

        $event = SomeEvent::fromContent([])->withHeader(Header::EVENT_TIME, 'some_datetime');

        $context = $this->newContext();
        $context->currentStreamName = 'customer';

        $this->option->getDispatchSignal()->willReturn(false)->shouldBeCalledOnce();
        $this->gap->detect('customer', 12, 'some_datetime')->willReturn(false)->shouldBeCalledOnce();
        $this->position->bind('customer', 12)->shouldBeCalledOnce();
        $this->counter->increment()->shouldBeCalledOnce();

        $this->assertTrue($processEvent($context, $event, 12, $this->repository->reveal()));
    }

    /**
     * @test
     */
    public function it_test_preprocess_event_for_persistent_projection_with_gap_detected(): void
    {
        $processEvent = new class extends EventProcessor
        {
            public function __invoke(Context $context, DomainEvent $event, int $key, ?ProjectorRepository $repository)
            {
                return $this->preProcess($context, $event, $key, $repository);
            }
        };

        $event = SomeEvent::fromContent([])->withHeader(Header::EVENT_TIME, 'some_datetime');

        $context = $this->newContext();
        $context->currentStreamName = 'customer';

        $this->option->getDispatchSignal()->willReturn(false)->shouldBeCalledOnce();
        $this->gap->detect('customer', 12, 'some_datetime')->willReturn(true)->shouldBeCalledOnce();
        $this->position->bind('customer', 12)->shouldNotBeCalled();
        $this->counter->increment()->shouldNotBeCalled();

        $this->assertFalse($processEvent($context, $event, 12, $this->repository->reveal()));
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_test_after_process_event_with_persist_block_size_not_reached(bool $stopProcess): void
    {
        $processEvent = new class extends EventProcessor
        {
            public function __invoke(Context $context, DomainEvent $event, int $key, ?ProjectorRepository $repository)
            {
                return $this->afterProcess($context, ['foo' => 'bar'], $repository);
            }
        };

        $context = $this->newContext();
        $context->currentStreamName = 'customer';
        $this->assertEmpty($context->state->get());

        $this->option->getPersistBlockSize()->willReturn(1000)->shouldBeCalledOnce();
        $this->counter->equals(1000)->willReturn(false)->shouldBeCalledOnce();

        $context->runner->stop($stopProcess);

        $this->assertNotEquals($stopProcess, $processEvent($context, SomeEvent::fromContent([]), 125, $this->repository->reveal()));
        $this->assertEquals(['foo' => 'bar'], $context->state->get());
    }

    /**
     * @test
     *
     * @dataProvider provideProjectionStatusWhichStopProjection
     */
    public function it_test_after_process_event_with_persist_block_size_reached_and_stop_projection(ProjectionStatus $projectionStatus): void
    {
        $processEvent = new class extends EventProcessor
        {
            public function __invoke(Context $context, DomainEvent $event, int $key, ?ProjectorRepository $repository)
            {
                return $this->afterProcess($context, ['foo' => 'bar'], $repository);
            }
        };

        $context = $this->newContext();
        $context->currentStreamName = 'customer';
        $this->assertEmpty($context->state->get());

        $this->option->getPersistBlockSize()->willReturn(1000)->shouldBeCalledOnce();
        $this->counter->equals(1000)->willReturn(true)->shouldBeCalledOnce();

        $this->repository->store()->shouldBeCalledOnce();
        $this->counter->reset()->shouldBeCalledOnce();

        $this->repository->disclose()->willReturn($projectionStatus)->shouldBeCalledOnce();

        $this->assertFalse($processEvent($context, SomeEvent::fromContent([]), 125, $this->repository->reveal()));
        $this->assertEquals(['foo' => 'bar'], $context->state->get());
        $this->assertEquals($projectionStatus, $context->status);
    }

    /**
     * @test
     *
     * @dataProvider provideProjectionStatusWhichKeepProjectionRunning
     */
    public function it_test_after_process_event_with_persist_block_size_reached_and_keep_projection_running(ProjectionStatus $projectionStatus): void
    {
        $processEvent = new class extends EventProcessor
        {
            public function __invoke(Context $context, DomainEvent $event, int $key, ?ProjectorRepository $repository)
            {
                return $this->afterProcess($context, [], $repository);
            }
        };

        $context = $this->newContext();
        $context->currentStreamName = 'customer';
        $this->assertEmpty($context->state->get());

        $this->option->getPersistBlockSize()->willReturn(1000)->shouldBeCalledOnce();
        $this->counter->equals(1000)->willReturn(true)->shouldBeCalledOnce();

        $this->repository->store()->shouldBeCalledOnce();
        $this->counter->reset()->shouldBeCalledOnce();

        $this->repository->disclose()->willReturn($projectionStatus)->shouldBeCalledOnce();

        $this->assertTrue($processEvent($context, SomeEvent::fromContent([]), 125, $this->repository->reveal()));
        $this->assertEmpty($context->state->get());
        $this->assertEquals($projectionStatus, $context->status);
    }

    public function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }

    public function provideProjectionStatusWhichStopProjection(): Generator
    {
        yield [ProjectionStatus::STOPPING];
        yield [ProjectionStatus::RESETTING];
        yield [ProjectionStatus::DELETING];
        yield [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS];
    }

    public function provideProjectionStatusWhichKeepProjectionRunning(): Generator
    {
        yield [ProjectionStatus::IDLE];
        yield [ProjectionStatus::RUNNING];
    }
}
