<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideMockContext;
use function posix_kill;
use function pcntl_signal;
use function posix_getpid;

#[CoversClass(EventProcessor::class)]
final class QueryEventProcessorTest extends UnitTestCase
{
    use ProvideMockContext;

    #[Test]
    public function it_test_pre_process_event(): void
    {
        $processEvent = new class extends EventProcessor
        {
            public function __invoke(Context $context, DomainEvent $event, int $key, ?ProjectorRepository $repository)
            {
                return $this->preProcess($context, $event, $key, $repository);
            }
        };

        $context = $this->newContext();
        $context->currentStreamName = 'customer';

        $this->option->expects($this->once())->method('getSignal')->willReturn(false);
        $this->gap->expects($this->never())->method('detect');
        $this->position->expects($this->once())->method('bind')->with('customer', 125);

        $this->assertTrue($processEvent($context, SomeEvent::fromContent([]), 125, null));
    }

    #[Test]
    public function it_test_pre_process_event_with_signal_dispatch(): void
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

        $this->option->expects($this->once())->method('getSignal')->willReturn(true);
        $this->gap->expects($this->never())->method('detect');
        $this->position->expects($this->once())->method('bind')->with('customer', 125);

        $this->assertTrue($processEvent($context, SomeEvent::fromContent([]), 125, null));
        $this->assertEquals('signal handler dispatched', $result);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_test_after_process_event(bool $stopProcess): void
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

        $context->runner->stop($stopProcess);

        $this->assertNotEquals($stopProcess, $processEvent($context, SomeEvent::fromContent([]), 125, null));
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_test_after_process_event_with_null_state(bool $stopProcess): void
    {
        $processEvent = new class extends EventProcessor
        {
            public function __invoke(Context $context, DomainEvent $event, int $key, ?ProjectorRepository $repository)
            {
                return $this->afterProcess($context, null, $repository);
            }
        };

        $context = $this->newContext();
        $context->currentStreamName = 'customer';
        $context->state->put(['foo' => 'bar']);

        $context->runner->stop($stopProcess);

        $this->assertNotEquals($stopProcess, $processEvent($context, SomeEvent::fromContent([]), 125, null));
        $this->assertEquals(['foo' => 'bar'], $context->state->get());
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
