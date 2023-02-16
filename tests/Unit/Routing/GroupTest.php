<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Tests\Stubs\GroupStub;
use Chronhub\Storm\Reporter\ReportCommand;
use Chronhub\Storm\Producer\ProducerStrategy;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Tests\Double\NoOpMessageSubscriber;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;

final class GroupTest extends UnitTestCase
{
    private GroupStub $group;

    protected function setUp(): void
    {
        $this->group = new GroupStub('default', new CollectRoutes(new AliasFromClassName()));
    }

    /**
     * @test
     */
    public function it_test_default_group(): void
    {
        $group = $this->group;

        $this->assertNull($group->reporterConcrete());
        $this->assertNull($group->reporterServiceId());
        $this->assertNull($group->trackerId());
        $this->assertNull($group->messageHandlerMethodName());
        $this->assertNull($group->producerServiceId());
        $this->assertNull($group->queue());
        $this->assertEmpty($group->messageDecorators());
        $this->assertEmpty($group->messageSubscribers());
    }

    /**
     * @test
     */
    public function it_set_group_properties(): void
    {
        $group = $this->group;

        $group
            ->withReporterConcreteClass(ReportCommand::class)
            ->withReporterServiceId('reporter.command.default')
            ->withMessageHandlerMethodName('command')
            ->withProducerStrategy('sync')
            ->withProducerServiceId('message.producer.id')
            ->withQueue(['connection' => 'redis', 'name' => 'transaction'])
            ->withTrackerId(TrackMessage::class)
            ->withMessageDecorators(new NoOpMessageDecorator())
            ->withMessageSubscribers(
                new NoOpMessageSubscriber(Reporter::DISPATCH_EVENT, 1),
                new NoOpMessageSubscriber(Reporter::FINALIZE_EVENT, -1),
            );

        $this->assertEquals(ReportCommand::class, $group->reporterConcrete());
        $this->assertEquals('reporter.command.default', $group->reporterServiceId());
        $this->assertEquals(TrackMessage::class, $group->trackerId());
        $this->assertEquals('command', $group->messageHandlerMethodName());
        $this->assertEquals(ProducerStrategy::SYNC, $group->producerStrategy());
        $this->assertEquals('message.producer.id', $group->producerServiceId());
        $this->assertEquals(['connection' => 'redis', 'name' => 'transaction'], $group->queue());
        $this->assertCount(1, $group->messageDecorators());
        $this->assertCount(2, $group->messageSubscribers());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_reporter_class_is_not_a_valid_class_name(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Reporter concrete class reporter.command.default must be an instance of '.Reporter::class);

        $group = $this->group;

        $group->withReporterConcreteClass('reporter.command.default');
    }

    /**
     * @test
     */
    public function it_raise_exception_when_producer_strategy_is_unknown_on_set(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Invalid message producer key: unknown_strategy');

        $group = $this->group;

        $group->withProducerStrategy('unknown_strategy');
    }

    /**
     * @test
     */
    public function it_raise_exception_when_producer_strategy_is_null_on_get(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Producer strategy can not be null');

        $group = $this->group;

        $group->producerStrategy();
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $group = $this->group;
        $group->withProducerStrategy('sync');

        $this->assertEquals([
            'command' => [
                'default' => [
                    'group' => [
                        'service_id' => null,
                        'concrete' => null,
                        'tracker_id' => null,
                        'handler_method_name' => null,
                        'message_decorators' => [],
                        'message_subscribers' => [],
                        'producer_strategy' => 'sync',
                        'producer_service_id' => null,
                        'queue' => null,
                    ],
                    'routes' => [],
                ],
            ],
        ], $group->jsonSerialize());
    }
}
