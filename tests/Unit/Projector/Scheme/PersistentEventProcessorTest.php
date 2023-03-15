<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Tests\Unit\Projector\Mock\ProvideMockContext;
use function posix_kill;
use function pcntl_signal;
use function posix_getpid;

#[CoversClass(EventProcessor::class)]
final class PersistentEventProcessorTest extends UnitTestCase
{
    use ProvideMockContext;

    #[Test]
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

        $this->option->expects($this->once())->method('getSignal')->willReturn(true);
        $this->gap->expects($this->once())->method('detect')->with('customer', 14, 'some_datetime')->willReturn(false);
        $this->position->expects($this->once())->method('bind')->with('customer', 14);
        $this->counter->expects($this->once())->method('increment');

        $this->assertTrue($processEvent($context, $event, 14, $this->repository));
        $this->assertEquals('signal handler dispatched', $result);
    }

    #[Test]
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

        $this->option->expects($this->once())->method('getSignal')->willReturn(false);
        $this->gap->expects($this->once())->method('detect')->with('customer', 12, 'some_datetime')->willReturn(false);
        $this->position->expects($this->once())->method('bind')->with('customer', 12);
        $this->counter->expects($this->once())->method('increment');

        $this->assertTrue($processEvent($context, $event, 12, $this->repository));
    }

    #[Test]
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

        $this->option->expects($this->once())->method('getSignal')->willReturn(false);
        $this->gap->expects($this->once())->method('detect')->with('customer', 14, 'some_datetime')->willReturn(true);
        $this->position->expects($this->never())->method('bind');
        $this->counter->expects($this->never())->method('increment');

        $this->assertFalse($processEvent($context, $event, 14, $this->repository));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideBoolean')]
    #[Test]
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

        $this->counter->expects($this->once())->method('isReached')->willReturn(false);

        $context->runner->stop($stopProcess);

        $this->assertNotEquals($stopProcess, $processEvent($context, SomeEvent::fromContent([]), 125, $this->repository));
        $this->assertEquals(['foo' => 'bar'], $context->state->get());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideProjectionStatusWhichStopProjection')]
    #[Test]
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

        $this->counter->expects($this->once())->method('isReached')->willReturn(true);
        $this->repository->expects($this->once())->method('store');
        $this->counter->expects($this->once())->method('reset');
        $this->repository->expects($this->once())->method('disclose')->willReturn($projectionStatus);

        $this->assertFalse($processEvent($context, SomeEvent::fromContent([]), 125, $this->repository));
        $this->assertEquals(['foo' => 'bar'], $context->state->get());
        $this->assertEquals($projectionStatus, $context->status);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideProjectionStatusWhichKeepProjectionRunning')]
    #[Test]
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

        $this->counter->expects($this->once())->method('isReached')->willReturn(true);
        $this->repository->expects($this->once())->method('store');
        $this->counter->expects($this->once())->method('reset');
        $this->repository->expects($this->once())->method('disclose')->willReturn($projectionStatus);

        $this->assertTrue($processEvent($context, SomeEvent::fromContent([]), 125, $this->repository));
        $this->assertEmpty($context->state->get());
        $this->assertEquals($projectionStatus, $context->status);
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }

    public static function provideProjectionStatusWhichStopProjection(): Generator
    {
        yield [ProjectionStatus::STOPPING];
        yield [ProjectionStatus::RESETTING];
        yield [ProjectionStatus::DELETING];
        yield [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS];
    }

    public static function provideProjectionStatusWhichKeepProjectionRunning(): Generator
    {
        yield [ProjectionStatus::IDLE];
        yield [ProjectionStatus::RUNNING];
    }
}
