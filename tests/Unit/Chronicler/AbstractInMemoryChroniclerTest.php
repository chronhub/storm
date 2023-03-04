<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Closure;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Chronicler\EventChronicler;
use Chronhub\Storm\Contracts\Tracker\Listener;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Chronicler\AbstractChroniclerProvider;
use Chronhub\Storm\Contracts\Chronicler\StreamSubscriber;
use Chronhub\Storm\Chronicler\TransactionalEventChronicler;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalChronicler;
use Chronhub\Storm\Chronicler\Exceptions\InvalidArgumentException;

final class AbstractInMemoryChroniclerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_raise_exception_when_chronicler_is_already_eventable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to decorate a chronicler which is already decorated');

        $chronicler = $this->createMock(EventableChronicler::class);

        $container = $this->createMock(ContainerInterface::class);
        $containerAsClosure = fn (): ContainerInterface => $container;

        $provider = new class($containerAsClosure, $chronicler) extends AbstractChroniclerProvider
        {
            public function __construct(Closure $containerAsClosure, private readonly Chronicler $chronicler)
            {
                parent::__construct($containerAsClosure);
            }

            public function resolve(string $name, array $config): Chronicler
            {
                return $this->decorateChronicler($this->chronicler, null);
            }
        };

        $provider->resolve('foo', []);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_stream_tracker_missing_to_decorate_chronicler(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to decorate chronicler');

        $chronicler = $this->createMock(Chronicler::class);
        $container = $this->createMock(ContainerInterface::class);
        $containerAsClosure = fn (): ContainerInterface => $container;

        $provider = new class($containerAsClosure, $chronicler) extends AbstractChroniclerProvider
        {
            public function __construct(Closure $containerAsClosure, private readonly Chronicler $chronicler)
            {
                parent::__construct($containerAsClosure);
            }

            public function resolve(string $name, array $config): Chronicler
            {
                return $this->decorateChronicler($this->chronicler, null);
            }
        };

        $provider->resolve('foo', []);
    }

    /**
     * @test
     */
    public function it_resolve_tracker_id_given_in_configuration(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')->with('tracker.stream.default')->willReturn(new TrackStream());
        $containerAsClosure = fn (): ContainerInterface => $container;

        $chronicler = $this->createMock(Chronicler::class);

        $provider = new class($containerAsClosure, $chronicler) extends AbstractChroniclerProvider
        {
            public function __construct(Closure $containerAsClosure,
                                        private readonly Chronicler $chronicler)
            {
                parent::__construct($containerAsClosure);
            }

            public function resolve(string $name, array $config): Chronicler
            {
                $streamTracker = $this->resolveStreamTracker($config);

                TestCase::assertEquals(TrackStream::class, $streamTracker::class);

                return $this->decorateChronicler($this->chronicler, $streamTracker);
            }
        };

        $chronicler = $provider->resolve('foo', [
            'tracking' => [
                'tracker_id' => 'tracker.stream.default',
            ],
        ]);

        $this->assertEquals(EventChronicler::class, $chronicler::class);
    }

    /**
     * @test
     */
    public function it_resolve_transactional_tracker_id_given_in_config(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')->with('tracker.stream.transactional')->willReturn(new TrackTransactionalStream());

        $containerAsClosure = fn (): ContainerInterface => $container;

        $chronicler = $this->createMock(TransactionalChronicler::class);

        $provider = new class($containerAsClosure, $chronicler) extends AbstractChroniclerProvider
        {
            public function __construct(Closure $containerAsClosure,
                                        private readonly Chronicler $chronicler)
            {
                parent::__construct($containerAsClosure);
            }

            public function resolve(string $name, array $config): Chronicler
            {
                $streamTracker = $this->resolveStreamTracker($config);

                TestCase::assertEquals(TrackTransactionalStream::class, $streamTracker::class);

                return $this->decorateChronicler($this->chronicler, $streamTracker);
            }
        };

        $chronicler = $provider->resolve('foo', [
            'tracking' => [
                'tracker_id' => 'tracker.stream.transactional',
            ],
        ]);

        $this->assertEquals(TransactionalEventChronicler::class, $chronicler::class);
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_configuration_when_chronicler_is_not_transactional_as_stream_tracker(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration to decorate chronicler from chronicler provider:');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')->with('tracker.stream.transactional')->willReturn(new TrackTransactionalStream());

        $containerAsClosure = fn (): ContainerInterface => $container;

        $chronicler = $this->createMock(Chronicler::class);

        $provider = new class($containerAsClosure, $chronicler) extends AbstractChroniclerProvider
        {
            public function __construct(Closure $containerAsClosure,
                                        private readonly Chronicler $chronicler)
            {
                parent::__construct($containerAsClosure);
            }

            public function resolve(string $name, array $config): Chronicler
            {
                $streamTracker = $this->resolveStreamTracker($config);

                TestCase::assertEquals(TrackTransactionalStream::class, $streamTracker::class);

                return $this->decorateChronicler($this->chronicler, $streamTracker);
            }
        };

        $chronicler = $provider->resolve('foo', [
            'tracking' => [
                'tracker_id' => 'tracker.stream.transactional',
            ],
        ]);

        $this->assertEquals(TransactionalEventChronicler::class, $chronicler::class);
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_configuration_when_stream_tracker_is_not_transactional_as_chronicler(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration to decorate chronicler from chronicler provider:');

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')->with('tracker.stream.not_transactional')->willReturn(new TrackStream());

        $containerAsClosure = fn (): ContainerInterface => $container;

        $chronicler = $this->createMock(TransactionalChronicler::class);

        $provider = new class($containerAsClosure, $chronicler) extends AbstractChroniclerProvider
        {
            public function __construct(Closure $containerAsClosure,
                                        private readonly Chronicler $chronicler)
            {
                parent::__construct($containerAsClosure);
            }

            public function resolve(string $name, array $config): Chronicler
            {
                $streamTracker = $this->resolveStreamTracker($config);

                TestCase::assertEquals(TrackStream::class, $streamTracker::class);

                return $this->decorateChronicler($this->chronicler, $streamTracker);
            }
        };

        $chronicler = $provider->resolve('foo', [
            'tracking' => [
                'tracker_id' => 'tracker.stream.not_transactional',
            ],
        ]);

        $this->assertEquals(TransactionalEventChronicler::class, $chronicler::class);
    }

    /**
     * @test
     */
    public function it_attach_stream_subscribers_resolved_from_container_or_as_instance_to_eventable_chronicler(): void
    {
        $tracker = new TrackStream();

        $this->assertEmpty($tracker->listeners());

        $noOpStreamSubscriber = new class implements StreamSubscriber
        {
            public function attachToChronicler(EventableChronicler $chronicler): void
            {
                $chronicler->subscribe('foo', function (): void {
                    //
                }, 1000);
            }

            public function detachFromChronicler(EventableChronicler $chronicler): void
            {
                //
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap([
                ['tracker.stream.default', $tracker],
                ['stream.subscribers.no_op', $noOpStreamSubscriber],
            ]);

        $containerAsClosure = fn (): ContainerInterface => $container;

        $chronicler = $this->createMock(Chronicler::class);

        $provider = new class($containerAsClosure, $chronicler) extends AbstractChroniclerProvider
        {
            public function __construct(Closure $containerAsClosure,
                                        private readonly Chronicler $chronicler)
            {
                parent::__construct($containerAsClosure);
            }

            public function resolve(string $name, array $config): Chronicler
            {
                $streamTracker = $this->resolveStreamTracker($config);

                TestCase::assertEquals(TrackStream::class, $streamTracker::class);

                $chronicler = $this->decorateChronicler($this->chronicler, $streamTracker);

                $this->attachStreamSubscribers($chronicler, $config['tracking']['subscribers'] ?? []);

                return $chronicler;
            }
        };

        $provider->resolve('foo', [
            'tracking' => [
                'tracker_id' => 'tracker.stream.default',
                'subscribers' => [
                    'stream.subscribers.no_op',
                    $noOpStreamSubscriber,
                ],
            ],
        ]);

        $noOpStreamSubscribers = $tracker->listeners()->filter(fn (Listener $listener): bool => $listener->name() === 'foo');

        $this->assertCount(2, $noOpStreamSubscribers);
    }
}
