<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use stdClass;
use Generator;
use Chronhub\Storm\Message\Message;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Message\MessageFactory;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\Double\SomeQuery;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;

final class MessageFactoryTest extends ProphecyTestCase
{
    /**
     * @test
     */
    public function it_create_message_from_array(): void
    {
        $expectedMessage = new Message(new stdClass());

        $this->messageSerializer
            ->unserializeContent(['foo' => 'bar'])
            ->willYield([$expectedMessage])
            ->shouldBeCalled();

        $factory = new MessageFactory($this->messageSerializer->reveal());

        $message = $factory(['foo' => 'bar']);

        $this->assertEquals($expectedMessage, $message);
    }

    /**
     * @test
     */
    public function it_create_message_from_object(): void
    {
        $expectedMessage = new Message(new stdClass());

        $factory = new MessageFactory($this->messageSerializer->reveal());

        $message = $factory($expectedMessage);

        $this->assertEquals($expectedMessage, $message);
    }

    /**
     * @test
     *
     * @dataProvider provideDomain
     */
    public function it_create_message_from_domain_instance(Reporting $domain): void
    {
        $factory = new MessageFactory($this->messageSerializer->reveal());

        $message = $factory($domain);

        $this->assertEquals($domain, $message->event());
    }

    /**
     * @test
     *
     * @dataProvider provideDomain
     */
    public function it_create_message_from_event_instance_with_headers(Reporting $domain): void
    {
        $expectedEvent = $domain->withHeader('some', 'header');

        $factory = new MessageFactory($this->messageSerializer->reveal());

        $message = $factory($expectedEvent);

        $this->assertEquals($expectedEvent, $message->event());
        $this->assertEquals($expectedEvent->headers(), $message->event()->headers());
    }

    public function provideDomain(): Generator
    {
        $content = ['foo' => 'bar'];

        yield [SomeCommand::fromContent($content)];
        yield [SomeEvent::fromContent($content)];
        yield [SomeQuery::fromContent($content)];
    }

    private readonly MessageSerializer|ObjectProphecy $messageSerializer;

    public function setUp(): void
    {
        parent::setUp();

        $this->messageSerializer = $this->prophesize(MessageSerializer::class);
    }
}
