<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use stdClass;
use Generator;
use Chronhub\Storm\Routing\Route;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\Double\SomeQuery;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;

final class RouteTest extends UnitTestCase
{
    /**
     * @test
     *
     * @dataProvider provideMessageClassName
     */
    public function it_can_be_constructed_with_message_class_name(string $messageName): void
    {
        $route = new Route($messageName);

        $this->assertEquals($route->getOriginalMessageName(), $messageName);
        $this->assertEquals($route->getMessageName(), $messageName);
        $this->assertNull($route->getQueueOptions());
        $this->assertEmpty($route->getMessageHandlers());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_message_name_is_not_a_valid_class_name(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Message name must be a valid class name, got foo');

        new Route('foo');
    }

    /**
     * @test
     *
     * @dataProvider provideMessageClassName
     */
    public function it_set_message_alias(string $messageName): void
    {
        $route = new Route($messageName);

        $this->assertEquals($route->getOriginalMessageName(), $messageName);
        $this->assertEquals($route->getMessageName(), $messageName);

        $route->alias('some-alias');

        $this->assertEquals('some-alias', $route->getMessageName());
        $this->assertEquals($route->getOriginalMessageName(), $messageName);
    }

    /**
     * @test
     *
     * @dataProvider provideMessageHandler
     */
    public function it_add_message_handler(string|object $messageHandler): void
    {
        $route = new Route(SomeCommand::class);
        $route->to($messageHandler);

        $this->assertEquals([$messageHandler], $route->getMessageHandlers());
    }

    /**
     * @test
     *
     * @dataProvider provideMessageHandler
     */
    public function it_merge_message_handlers(): void
    {
        $route = new Route(SomeCommand::class);

        $route->to('some_message_handler');
        $route->to('another_message_handler');

        $this->assertEquals(['some_message_handler', 'another_message_handler'], $route->getMessageHandlers());
    }

    /**
     * @test
     */
    public function it_set_queue_options(): void
    {
        $route = new Route(SomeCommand::class);
        $route->onQueue(['connection' => 'redis', 'name' => 'default']);

        $this->assertEquals(['connection' => 'redis', 'name' => 'default'], $route->getQueueOptions());
    }

    /**
     * @test
     */
    public function it_return_null_queue_options_when_argument_is_empty(): void
    {
        $route = new Route(SomeCommand::class);
        $route->onQueue();

        $this->assertNull($route->getQueueOptions());
    }

    /**
     * @test
     */
    public function it_serialize_route(): void
    {
        $route = new Route(SomeCommand::class);

        $this->assertEquals([
            'message_name' => SomeCommand::class,
            'original_message_name' => SomeCommand::class,
            'message_handlers' => [],
            'queue_options' => null,
        ], $route->jsonSerialize());
    }

    /**
     * @test
     */
    public function it_serialize_full_route(): void
    {
        $route = new Route(SomeCommand::class);

        $route->alias('some-alias');
        $route->to('some_message_handler');
        $route->onQueue(['connection' => 'rabbitmq', 'name' => 'transaction']);

        $this->assertEquals([
            'message_name' => 'some-alias',
            'original_message_name' => SomeCommand::class,
            'message_handlers' => ['some_message_handler'],
            'queue_options' => ['connection' => 'rabbitmq', 'name' => 'transaction'],
        ], $route->jsonSerialize());
    }

    public function provideMessageClassName(): Generator
    {
        yield [SomeCommand::class];
        yield [SomeEvent::class];
        yield [SomeQuery::class];
        yield [stdClass::class];
    }

    public function provideMessageHandler(): Generator
    {
        yield ['foo'];

        yield [static function () {
            //
        }];

        yield [new class()
        {
            public function __invoke(object $event): void
            {
            }
        }];
    }
}
