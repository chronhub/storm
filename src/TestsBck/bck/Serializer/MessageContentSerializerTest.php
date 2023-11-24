<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Serializer\MessageContentSerializer;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeQuery;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

#[CoversClass(MessageContentSerializer::class)]
final class MessageContentSerializerTest extends UnitTestCase
{
    #[DataProvider('provideMessage')]
    public function testSerializeContent(Reporting $report): void
    {
        $contentSerializer = new MessageContentSerializer();

        $content = $contentSerializer->serialize($report);

        $this->assertEquals(['name' => 'steph bug'], $content);
    }

    public function testDeserializePayloadToReportingInstance(): void
    {
        $contentSerializer = new MessageContentSerializer();

        $command = $contentSerializer->deserialize(
            SomeCommand::class,
            ['content' => ['name' => 'steph bug']]
        );

        $this->assertInstanceOf(SomeCommand::class, $command);
        $this->assertEquals(['name' => 'steph bug'], $command->toContent());
    }

    public function testExceptionRaisedWithInvalidSourceToDeserialize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Invalid source to deserialize');

        (new MessageContentSerializer())->deserialize(stdClass::class, []);
    }

    public static function provideMessage(): Generator
    {
        yield [new SomeCommand(['name' => 'steph bug'])];
        yield [new SomeEvent(['name' => 'steph bug'])];
        yield [new SomeQuery(['name' => 'steph bug'])];
    }
}
