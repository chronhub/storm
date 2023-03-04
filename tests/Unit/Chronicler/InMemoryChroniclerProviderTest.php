<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Psr\Container\ContainerInterface;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Chronicler\TrackStream;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Stream\DetermineStreamCategory;
use Chronhub\Storm\Contracts\Stream\StreamCategory;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Chronicler\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Chronicler\InMemory\InMemoryChroniclerProvider;
use Chronhub\Storm\Chronicler\InMemory\StandaloneInMemoryChronicler;
use Chronhub\Storm\Chronicler\InMemory\TransactionalInMemoryChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;

final class InMemoryChroniclerProviderTest extends UnitTestCase
{
    private MockObject|ContainerInterface $container;

    public function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @test
     */
    public function it_create_standalone_in_memory_chronicler_instance(): void
    {
        $this->container
            ->expects($this->any())
            ->method('get')
            ->with(StreamCategory::class)
            ->willReturn(new DetermineStreamCategory());

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerProvider($containerAsClosure);

        $config = ['standalone' => []];
        $chronicler = $provider->resolve('standalone', $config);

        $this->assertEquals(StandaloneInMemoryChronicler::class, $chronicler::class);
        $this->assertEquals($chronicler, $provider->resolve('standalone', $config));
        $this->assertNotSame($chronicler, $provider->resolve('standalone', $config));
    }

    /**
     * @test
     */
    public function it_create_transactional_in_memory_chronicler_instance(): void
    {
        $this->container
            ->expects($this->any())
            ->method('get')
            ->with(StreamCategory::class)
            ->willReturn(new DetermineStreamCategory());

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerProvider($containerAsClosure);

        $config = ['transactional' => []];
        $chronicler = $provider->resolve('transactional', $config);

        $this->assertEquals(TransactionalInMemoryChronicler::class, $chronicler::class);
        $this->assertEquals($chronicler, $provider->resolve('transactional', $config));
        $this->assertNotSame($chronicler, $provider->resolve('transactional', $config));
    }

    /**
     * @test
     */
    public function it_create_eventable_in_memory_chronicler_instance(): void
    {
        $this->container
            ->method('get')
            ->willReturnMap([
                [StreamCategory::class, new DetermineStreamCategory()],
                [TrackStream::class, new TrackStream()],
            ]);

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerProvider($containerAsClosure);

        $config = [
            'tracking' => [
                'tracker_id' => TrackStream::class,
                'subscribers' => [],
            ],
        ];

        $chronicler = $provider->resolve('eventable', $config);

        $this->assertInstanceOf(EventableChronicler::class, $chronicler);
        $this->assertEquals(StandaloneInMemoryChronicler::class, $chronicler->innerChronicler()::class);
        $this->assertEquals($chronicler, $provider->resolve('eventable', $config));
        $this->assertNotSame($chronicler, $provider->resolve('eventable', $config));
    }

    /**
     * @test
     */
    public function it_create_transactional_eventable_in_memory_chronicler_instance(): void
    {
        $this->container
            ->method('get')
            ->willReturnMap([
                [StreamCategory::class, new DetermineStreamCategory()],
                [TrackTransactionalStream::class, new TrackTransactionalStream()],
            ]);

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerProvider($containerAsClosure);

        $config = [
            'tracking' => [
                'tracker_id' => TrackTransactionalStream::class,
                'subscribers' => [],
            ],
        ];

        $chronicler = $provider->resolve('transactional_eventable', $config);

        $this->assertInstanceOf(TransactionalEventableChronicler::class, $chronicler);
        $this->assertInstanceOf(EventableChronicler::class, $chronicler);
        $this->assertEquals(TransactionalInMemoryChronicler::class, $chronicler->innerChronicler()::class);
        $this->assertEquals($chronicler, $provider->resolve('transactional_eventable', $config));
        $this->assertNotSame($chronicler, $provider->resolve('transactional_eventable', $config));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_in_memory_driver_is_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('In memory chronicler provider foo is not defined');

        $config = ['tracking' => []];

        $this->container = $this->createMock(ContainerInterface::class);
        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerProvider($containerAsClosure);

        $provider->resolve('foo', $config);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_eventable_chronicler_has_not_tracker(): void
    {
        $expectedClass = StandaloneInMemoryChronicler::class;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to decorate chronicler '.$expectedClass.', stream tracker is not defined or invalid');

        $config = ['tracking' => []];

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container
            ->expects($this->any())
            ->method('get')
            ->with(StreamCategory::class)
            ->willReturn(new DetermineStreamCategory());

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerProvider($containerAsClosure);

        $provider->resolve('eventable', $config);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_transactional_eventable_chronicler_has_not_tracker(): void
    {
        $expectedClass = TransactionalInMemoryChronicler::class;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to decorate chronicler '.$expectedClass.', stream tracker is not defined or invalid');

        $config = ['tracking' => []];

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container
            ->expects($this->any())
            ->method('get')
            ->with(StreamCategory::class)
            ->willReturn(new DetermineStreamCategory());

        $containerAsClosure = fn (): ContainerInterface => $this->container;

        $provider = new InMemoryChroniclerProvider($containerAsClosure);

        $provider->resolve('transactional_eventable', $config);
    }
}
