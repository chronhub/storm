<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Serializer\ProjectorJsonSerializer;

#[CoversClass(ProjectorJsonSerializer::class)]
final class ProjectorJsonSerializerTest extends UnitTestCase
{
    #[Test]
    public function it_encode_data(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals('{"foo":"bar"}', $serializer->encode(['foo' => 'bar']));
    }

    #[Test]
    public function it_decode_data(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals(['foo' => 'bar'], $serializer->decode('{"foo":"bar"}'));
    }

    #[Test]
    public function it_serialize_empty_array(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals('{}', $serializer->encode([]));
    }

    #[Test]
    public function it_deserialize_string_to_empty_array(): void
    {
        $serializer = new ProjectorJsonSerializer();

        $this->assertEquals([], $serializer->decode('{}'));
    }
}
