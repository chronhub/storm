<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use stdClass;
use Generator;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Message\MessageFactory;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\Double\SomeQuery;
use Chronhub\Storm\Tests\Double\SomeCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;

final class MessageFactoryTest extends UnitTestCase
{
    #[Test]
    public function it_create_message_from_array(): void
    {
        $expectedMessage = new Message(SomeCommand::fromContent(['foo' => 'bar']));

        $this->messageSerializer->expects($this->once())
            ->method('deserializePayload')
            ->with(['foo' => 'bar'])
            ->willReturn($expectedMessage->event());

        $factory = new MessageFactory($this->messageSerializer);

        $message = $factory(['foo' => 'bar']);

        $this->assertEquals($expectedMessage, $message);
    }

    #[Test]
    public function it_create_message_from_object(): void
    {
        $expectedMessage = new Message(new stdClass());

        $this->messageSerializer->expects($this->never())->method('deserializePayload');
        $factory = new MessageFactory($this->messageSerializer);

        $message = $factory($expectedMessage);

        $this->assertEquals($expectedMessage, $message);
    }

    #[DataProvider('provideDomain')]
    #[Test]
    public function it_create_message_from_domain_instance(Reporting $domain): void
    {
        $factory = new MessageFactory($this->messageSerializer);

        $message = $factory($domain);

        $this->assertEquals($domain, $message->event());
    }

    #[DataProvider('provideDomain')]
    #[Test]
    public function it_create_message_from_event_instance_with_headers(Reporting $domain): void
    {
        $expectedEvent = $domain->withHeader('some', 'header');

        $factory = new MessageFactory($this->messageSerializer);

        $message = $factory($expectedEvent);

        $this->assertEquals($expectedEvent, $message->event());
        $this->assertEquals($expectedEvent->headers(), $message->event()->headers());
    }

    public static function provideDomain(): Generator
    {
        $content = ['foo' => 'bar'];

        yield [SomeCommand::fromContent($content)];
        yield [SomeEvent::fromContent($content)];
        yield [SomeQuery::fromContent($content)];
    }

    private MessageSerializer|MockObject $messageSerializer;

    public function setUp(): void
    {
        parent::setUp();

        $this->messageSerializer = $this->createMock(MessageSerializer::class);
    }
}
