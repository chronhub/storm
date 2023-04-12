<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;
use Chronhub\Storm\Contracts\Serializer\StreamEventSerializer;
use Chronhub\Storm\Serializer\DomainEventSerializer;
use Chronhub\Storm\Serializer\JsonSerializerFactory;
use Chronhub\Storm\Serializer\MessagingSerializer;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;

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

    public function testCreateMessageSerializerInstance(): void
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

    public function testCreateStreamSerializerInstance(): void
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

    public function testMergeMessageNormalizers(): void
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

    public function testMergeStreamSerializerNormalizers(): void
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
