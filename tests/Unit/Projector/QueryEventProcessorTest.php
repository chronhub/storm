<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;
use function posix_kill;
use function pcntl_signal;
use function posix_getpid;

/**
 * @coversDefaultClass \Chronhub\Storm\Projector\Scheme\EventProcessor
 */
final class QueryEventProcessorTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy;

    /**
     * @test
     */
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

        $this->option->getDispatchSignal()->willReturn(false)->shouldBeCalledOnce();
        $this->gap->detect()->shouldNotBeCalled();
        $this->position->bind('customer', 125)->shouldBeCalledOnce();

        $this->assertTrue($processEvent($context, SomeEvent::fromContent([]), 125, null));
    }

    /**
     * @test
     */
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

        $this->option->getDispatchSignal()->willReturn(true)->shouldBeCalledOnce();
        $this->gap->detect()->shouldNotBeCalled();
        $this->position->bind('customer', 125)->shouldBeCalledOnce();

        $this->assertTrue($processEvent($context, SomeEvent::fromContent([]), 125, null));
        $this->assertEquals('signal handler dispatched', $result);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
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

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
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

    public function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
