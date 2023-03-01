<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Clock\PointInTime;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Serializer\MessagingSerializer;
use Chronhub\Storm\Serializer\DomainEventSerializer;
use Chronhub\Storm\Serializer\JsonSerializerFactory;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Chronhub\Storm\Contracts\Serializer\StreamEventSerializer;

final class JsonSerializerFactoryTest extends ProphecyTestCase
{
    private ObjectProphecy|ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container->get(SystemClock::class)->willReturn(new PointInTime())->shouldBeCalledOnce();
    }

    /**
     * @test
     */
    public function it_create_message_serializer(): void
    {
        $factory = new JsonSerializerFactory(fn () => $this->container->reveal());

        $serializer = $factory->createMessageSerializer();

        $this->assertInstanceOf(MessageSerializer::class, $serializer);
        $this->assertEquals(MessagingSerializer::class, $serializer::class);
    }

    /**
     * @test
     */
    public function it_create_stream_serializer(): void
    {
        $factory = new JsonSerializerFactory(fn () => $this->container->reveal());

        $serializer = $factory->createStreamSerializer();

        $this->assertInstanceOf(StreamEventSerializer::class, $serializer);
        $this->assertEquals(DomainEventSerializer::class, $serializer::class);
    }

    /**
     * @test
     */
    public function it_merge_message_normalizers(): void
    {
        $this->container->get(UidNormalizer::class)->willReturn(new UidNormalizer())->shouldBeCalledOnce();

        $factory = new JsonSerializerFactory(fn () => $this->container->reveal());

        $serializer = $factory->createMessageSerializer(null, UidNormalizer::class);
    }

    /**
     * @test
     */
    public function it_merge_stream_normalizers(): void
    {
        $this->container->get(UidNormalizer::class)->willReturn(new UidNormalizer())->shouldBeCalledOnce();

        $factory = new JsonSerializerFactory(fn () => $this->container->reveal());

        $factory->createStreamSerializer(null, UidNormalizer::class);
    }
}
