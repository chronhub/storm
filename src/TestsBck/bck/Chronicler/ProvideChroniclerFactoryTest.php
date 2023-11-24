<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\EventChronicler;
use Chronhub\Storm\Chronicler\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Chronicler\ProvideChroniclerFactory;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Chronicler\TransactionalEventChronicler;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerFactory;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\StreamSubscriber;
use Chronhub\Storm\Contracts\Chronicler\TransactionalChronicler;
use Chronhub\Storm\Contracts\Tracker\Listener;
use Chronhub\Storm\Tests\UnitTestCase;
use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(ProvideChroniclerFactory::class)]
final class ProvideChroniclerFactoryTest extends UnitTestCase
{
    private ContainerInterface|MockObject $container;

    private Closure $containerAsClosure;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->containerAsClosure = fn (): ContainerInterface => $this->container;
    }

    public function testExceptionRaisedWhenEventStoreGivenIsAlreadyADecorator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to decorate a chronicler which is already decorated');

        $chronicler = $this->createMock(EventableChronicler::class);

        $factory = $this->newChroniclerFactory($chronicler, function (Chronicler|MockObject $chronicler): Chronicler {
            /** @var ChroniclerFactory $this */
            /** @phpstan-ignore-next-line  */
            return $this->decorateChronicler($chronicler, null);
        });

        $factory->createEventStore('foo', []);
    }

    public function testExceptionRaisedWhenStreamTrackerMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to decorate chronicler');

        $chronicler = $this->createMock(Chronicler::class);

        $factory = $this->newChroniclerFactory($chronicler, function (Chronicler|MockObject $chronicler): Chronicler {
            /** @var ChroniclerFactory $this */
            /** @phpstan-ignore-next-line  */
            return $this->decorateChronicler($chronicler, null);
        });

        $factory->createEventStore('foo', []);
    }

    public function testTrackerIdResolvedFromIoc(): void
    {
        $chronicler = $this->createMock(Chronicler::class);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('tracker.stream.default')
            ->willReturn(new TrackStream());

        $factory = $this->newChroniclerFactory(
            $chronicler,
            function (Chronicler|MockObject $chronicler, array $config): Chronicler {
                /** @var ChroniclerFactory $this */

                /** @phpstan-ignore-next-line */
                $streamTracker = $this->resolveStreamTracker($config);

                TestCase::assertEquals(TrackStream::class, $streamTracker::class);

                /** @phpstan-ignore-next-line */
                return $this->decorateChronicler($chronicler, $streamTracker);
        });

        $chronicler = $factory->createEventStore('foo', ['tracking' => ['tracker_id' => 'tracker.stream.default']]);

        $this->assertEquals(EventChronicler::class, $chronicler::class);
    }

    public function testTransactionalTrackerIdResolvedFromIoc(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('tracker.stream.transactional')
            ->willReturn(new TrackTransactionalStream());

        $chronicler = $this->createMock(TransactionalChronicler::class);

        $factory = $this->newChroniclerFactory(
            $chronicler,
            function (Chronicler|MockObject $chronicler, array $config): Chronicler {
                /** @var ChroniclerFactory $this */

                /** @phpstan-ignore-next-line */
                $streamTracker = $this->resolveStreamTracker($config);

                TestCase::assertEquals(TrackTransactionalStream::class, $streamTracker::class);

                /** @phpstan-ignore-next-line */
                return $this->decorateChronicler($chronicler, $streamTracker);
        });

        $chronicler = $factory->createEventStore('foo', ['tracking' => ['tracker_id' => 'tracker.stream.transactional']]);

        $this->assertEquals(TransactionalEventChronicler::class, $chronicler::class);
    }

    public function testExceptionRaisedWithIncompatibleEventStoreAndStreamTracker(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration to decorate chronicler from chronicler provider');

        $this->container->expects($this->once())
            ->method('get')
            ->with('tracker.stream.transactional')
            ->willReturn(new TrackTransactionalStream());

        $chronicler = $this->createMock(Chronicler::class);

        $provider = $this->newChroniclerFactory(
            $chronicler,
            function (Chronicler|MockObject $chronicler, array $config): Chronicler {
                /** @var ChroniclerFactory $this */

                /** @phpstan-ignore-next-line */
                $streamTracker = $this->resolveStreamTracker($config);

                TestCase::assertEquals(TrackTransactionalStream::class, $streamTracker::class);

                /** @phpstan-ignore-next-line */
                return $this->decorateChronicler($chronicler, $streamTracker);
            });

        $chronicler = $provider->createEventStore('foo', ['tracking' => ['tracker_id' => 'tracker.stream.transactional']]);

        $this->assertEquals(TransactionalEventChronicler::class, $chronicler::class);
    }

    public function testExceptionRaisedWithIncompatibleEventStoreAndStreamTracker_2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration to decorate chronicler from chronicler provider');

        $this->container->expects($this->once())
            ->method('get')
            ->with('tracker.stream.not_transactional')
            ->willReturn(new TrackStream());

        $chronicler = $this->createMock(TransactionalChronicler::class);

        $factory = $this->newChroniclerFactory(
            $chronicler,
            function (Chronicler|MockObject $chronicler, array $config): Chronicler {
                /** @var ChroniclerFactory $this */

                /** @phpstan-ignore-next-line */
                $streamTracker = $this->resolveStreamTracker($config);

                TestCase::assertEquals(TrackStream::class, $streamTracker::class);

                /** @phpstan-ignore-next-line */
                return $this->decorateChronicler($chronicler, $streamTracker);
            }
        );

        $chronicler = $factory->createEventStore('foo', ['tracking' => ['tracker_id' => 'tracker.stream.not_transactional']]);

        $this->assertEquals(TransactionalEventChronicler::class, $chronicler::class);
    }

    public function testStreamSubscribersAttachedToEventableEventStore(): void
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

        $this->container
            ->method('get')
            ->willReturnMap([
                ['tracker.stream.default', $tracker],
                ['stream.subscribers.no_op', $noOpStreamSubscriber],
            ]);

        $chronicler = $this->createMock(Chronicler::class);

        $factory = $this->newChroniclerFactory(
            $chronicler,
            function (Chronicler|MockObject $chronicler, array $config): Chronicler {
                /** @var ChroniclerFactory $this */

                /** @phpstan-ignore-next-line */
                $streamTracker = $this->resolveStreamTracker($config);

                TestCase::assertEquals(TrackStream::class, $streamTracker::class);

                /** @phpstan-ignore-next-line */
                $chronicler = $this->decorateChronicler($chronicler, $streamTracker);

                /** @phpstan-ignore-next-line */
                $this->attachStreamSubscribers($chronicler, $config['tracking']['subscribers'] ?? []);

                return $chronicler;
            }
        );

        $factory->createEventStore('foo', [
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

    private function newChroniclerFactory(Chronicler $chronicler, callable $callback): ChroniclerFactory
    {
        $containerAsClosure = $this->containerAsClosure;

        return new class($containerAsClosure, $chronicler, $callback) implements ChroniclerFactory
        {
            use ProvideChroniclerFactory;

            private Closure $callback;

            public function __construct(
                Closure $containerAsClosure,
                public readonly Chronicler $chronicler,
                callable $callback
            ) {
                $this->container = $containerAsClosure();
                $this->callback = Closure::bind($callback, $this, self::class);
            }

            public function createEventStore(string $name, array $config): Chronicler
            {
                return ($this->callback)($this->chronicler, $config, $name);
            }
        };
    }
}
