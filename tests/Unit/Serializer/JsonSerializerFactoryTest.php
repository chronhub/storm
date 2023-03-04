<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Clock\PointInTime;
use Psr\Container\ContainerInterface;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Serializer\MessagingSerializer;
use Chronhub\Storm\Serializer\DomainEventSerializer;
use Chronhub\Storm\Serializer\JsonSerializerFactory;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Chronhub\Storm\Contracts\Serializer\StreamEventSerializer;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;

#[CoversClass(JsonSerializerFactory::class)]
final class JsonSerializerFactoryTest extends UnitTestCase
{
    private MockObject|ContainerInterface $container;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
    }

    #[Test]
    public function it_create_message_serializer(): void
    {
        $this->container->expects($this->any())
            ->method('get')
            ->with(SystemClock::class)
            ->willReturn(new PointInTime());

        $factory = new JsonSerializerFactory(fn () => $this->container);

        $serializer = $factory->createMessageSerializer();

        $this->assertInstanceOf(MessageSerializer::class, $serializer);
        $this->assertEquals(MessagingSerializer::class, $serializer::class);
    }

    #[Test]
    public function it_create_stream_serializer(): void
    {
        $this->container->expects($this->any())
            ->method('get')
            ->with(SystemClock::class)
            ->willReturn(new PointInTime());

        $factory = new JsonSerializerFactory(fn () => $this->container);

        $serializer = $factory->createStreamSerializer();

        $this->assertInstanceOf(StreamEventSerializer::class, $serializer);
        $this->assertEquals(DomainEventSerializer::class, $serializer::class);
    }

    #[Test]
    public function it_merge_message_normalizers(): void
    {
        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [SystemClock::class, new PointInTime()],
                [UidNormalizer::class, new UidNormalizer()],
            ]);

        $factory = new JsonSerializerFactory(fn () => $this->container);

        $factory->createMessageSerializer(null, UidNormalizer::class, new DateIntervalNormalizer());
    }

    #[Test]
    public function it_merge_stream_normalizers(): void
    {
        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [SystemClock::class, new PointInTime()],
                [UidNormalizer::class, new UidNormalizer()],
            ]);

        $factory = new JsonSerializerFactory(fn () => $this->container);

        $factory->createStreamSerializer(null, UidNormalizer::class, new DateIntervalNormalizer());
    }
}
