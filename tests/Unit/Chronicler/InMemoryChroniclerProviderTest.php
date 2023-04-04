<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Chronicler\InMemory\InMemoryChroniclerFactory;
use Chronhub\Storm\Chronicler\InMemory\StandaloneInMemoryChronicler;
use Chronhub\Storm\Chronicler\InMemory\TransactionalInMemoryChronicler;
use Chronhub\Storm\Chronicler\ProvideChroniclerFactory;
use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use Chronhub\Storm\Contracts\Stream\StreamCategory;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use function sprintf;

#[CoversClass(InMemoryChroniclerFactory::class)]
#[CoversClass(ProvideChroniclerFactory::class)]
final class InMemoryChroniclerProviderTest extends UnitTestCase
{
    private MockObject|ContainerInterface $container;

    public function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testStandaloneInstance(): void
    {
        $this->container
            ->expects($this->any())
            ->method('get')
            ->with(StreamCategory::class)
            ->willReturn(new DetermineStreamCategory());

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerFactory($containerAsClosure);

        $config = ['standalone' => []];
        $chronicler = $provider->createEventStore('standalone', $config);

        $this->assertEquals(StandaloneInMemoryChronicler::class, $chronicler::class);
        $this->assertEquals($chronicler, $provider->createEventStore('standalone', $config));
        $this->assertNotSame($chronicler, $provider->createEventStore('standalone', $config));
    }

    public function testTransactionalInstance(): void
    {
        $this->container
            ->expects($this->any())
            ->method('get')
            ->with(StreamCategory::class)
            ->willReturn(new DetermineStreamCategory());

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerFactory($containerAsClosure);

        $config = ['transactional' => []];
        $chronicler = $provider->createEventStore('transactional', $config);

        $this->assertEquals(TransactionalInMemoryChronicler::class, $chronicler::class);
        $this->assertEquals($chronicler, $provider->createEventStore('transactional', $config));
        $this->assertNotSame($chronicler, $provider->createEventStore('transactional', $config));
    }

    public function testEventableInstance(): void
    {
        $this->container
            ->method('get')
            ->willReturnMap([
                [StreamCategory::class, new DetermineStreamCategory()],
                [TrackStream::class, new TrackStream()],
            ]);

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerFactory($containerAsClosure);

        $config = [
            'tracking' => [
                'tracker_id' => TrackStream::class,
                'subscribers' => [],
            ],
        ];

        $chronicler = $provider->createEventStore('eventable', $config);

        $this->assertInstanceOf(EventableChronicler::class, $chronicler);
        $this->assertEquals(StandaloneInMemoryChronicler::class, $chronicler->innerChronicler()::class);
        $this->assertEquals($chronicler, $provider->createEventStore('eventable', $config));
        $this->assertNotSame($chronicler, $provider->createEventStore('eventable', $config));
    }

    public function testTransactionalEventableInstance(): void
    {
        $this->container
            ->method('get')
            ->willReturnMap([
                [StreamCategory::class, new DetermineStreamCategory()],
                [TrackTransactionalStream::class, new TrackTransactionalStream()],
            ]);

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerFactory($containerAsClosure);

        $config = [
            'tracking' => [
                'tracker_id' => TrackTransactionalStream::class,
                'subscribers' => [],
            ],
        ];

        $chronicler = $provider->createEventStore('transactional_eventable', $config);

        $this->assertInstanceOf(TransactionalEventableChronicler::class, $chronicler);
        $this->assertInstanceOf(EventableChronicler::class, $chronicler);
        $this->assertEquals(TransactionalInMemoryChronicler::class, $chronicler->innerChronicler()::class);
        $this->assertEquals($chronicler, $provider->createEventStore('transactional_eventable', $config));
        $this->assertNotSame($chronicler, $provider->createEventStore('transactional_eventable', $config));
    }

    public function testExceptionRaisedWhenDriverIsNotDefined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('In memory chronicler provider foo is not defined');

        $config = ['tracking' => []];

        $this->container = $this->createMock(ContainerInterface::class);
        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerFactory($containerAsClosure);

        $provider->createEventStore('foo', $config);
    }

    public function testExceptionRaisedWhenExpectEventableInstanceWithNoTracker(): void
    {
        $expectedClass = StandaloneInMemoryChronicler::class;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('Unable to decorate chronicler %s, stream tracker is not defined or invalid', $expectedClass)
        );

        $config = ['tracking' => []];

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container
            ->expects($this->any())
            ->method('get')
            ->with(StreamCategory::class)
            ->willReturn(new DetermineStreamCategory());

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerFactory($containerAsClosure);

        $provider->createEventStore('eventable', $config);
    }

    public function testExceptionRaisedWhenExpectTransactionalEventableInstanceWithNoTracker(): void
    {
        $expectedClass = TransactionalInMemoryChronicler::class;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('Unable to decorate chronicler %s, stream tracker is not defined or invalid', $expectedClass)
        );

        $config = ['tracking' => []];

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container
            ->expects($this->any())
            ->method('get')
            ->with(StreamCategory::class)
            ->willReturn(new DetermineStreamCategory());

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerFactory($containerAsClosure);

        $provider->createEventStore('transactional_eventable', $config);
    }
}
