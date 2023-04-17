<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Message\MessageFactory;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeQuery;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

final class MessageFactoryTest extends UnitTestCase
{
    private MessageSerializer|MockObject $messageSerializer;

    public function setUp(): void
    {
        parent::setUp();

        $this->messageSerializer = $this->createMock(MessageSerializer::class);
    }

    public function testCreateMessageFromArray(): void
    {
        $expectedMessage = new Message(SomeCommand::fromContent(['foo' => 'bar']));

        $this->messageSerializer
            ->expects($this->once())
            ->method('deserializePayload')
            ->with(['foo' => 'bar'])
            ->willReturn($expectedMessage->event());

        $factory = new MessageFactory($this->messageSerializer);

        $message = $factory(['foo' => 'bar']);

        $this->assertEquals($expectedMessage, $message);
        $this->assertNotSame($expectedMessage, $message);
    }

    public function testCreateMessageFromObject(): void
    {
        $expectedMessage = new Message(new stdClass());

        $this->messageSerializer->expects($this->never())->method('deserializePayload');
        $factory = new MessageFactory($this->messageSerializer);

        $message = $factory($expectedMessage);

        $this->assertEquals($expectedMessage, $message);
        $this->assertNotSame($expectedMessage, $message);
    }

    #[DataProvider('provideDomain')]
    public function testCreateMessageFromDomainInstance(Reporting $domain): void
    {
        $factory = new MessageFactory($this->messageSerializer);

        $message = $factory($domain);

        $this->assertEquals($domain, $message->event());
        $this->assertNotSame($domain, $message->event());
    }

    #[DataProvider('provideDomain')]
    public function testCreateMessageFromEvenInstanceWithHeaders(Reporting $domain): void
    {
        $expectedEvent = $domain->withHeader('some', 'header');

        $factory = new MessageFactory($this->messageSerializer);

        $message = $factory($expectedEvent);

        $this->assertEquals($expectedEvent, $message->event());
        $this->assertNotSame($expectedEvent, $message->event());
        $this->assertEquals($expectedEvent->headers(), $message->event()->headers());
    }

    public static function provideDomain(): Generator
    {
        $content = ['foo' => 'bar'];

        yield [SomeCommand::fromContent($content)];
        yield [SomeEvent::fromContent($content)];
        yield [SomeQuery::fromContent($content)];
    }
}
