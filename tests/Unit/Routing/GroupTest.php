<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Routing\Group;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Tests\Stubs\GroupStub;
use Chronhub\Storm\Reporter\ReportCommand;
use Chronhub\Storm\Producer\ProducerStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Tests\Double\NoOpMessageSubscriber;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;

#[CoversClass(Group::class)]
final class GroupTest extends UnitTestCase
{
    private GroupStub $group;

    protected function setUp(): void
    {
        $this->group = new GroupStub('default', new CollectRoutes(new AliasFromClassName()));
    }

    #[Test]
    public function it_test_default_group(): void
    {
        $group = $this->group;

        $this->assertNull($group->reporterConcrete());
        $this->assertNull($group->reporterId());
        $this->assertNull($group->trackerId());
        $this->assertNull($group->handlerMethod());
        $this->assertNull($group->producerId());
        $this->assertNull($group->queue());
        $this->assertEmpty($group->decorators());
        $this->assertEmpty($group->subscribers());
    }

    #[Test]
    public function it_set_group_properties(): void
    {
        $group = $this->group;

        $group
            ->withReporterConcrete(ReportCommand::class)
            ->withReporterId('reporter.command.default')
            ->withHandlerMethod('command')
            ->withStrategy('sync')
            ->withProducerId('message.producer.id')
            ->withQueue(['connection' => 'redis', 'name' => 'transaction'])
            ->withTrackerId(TrackMessage::class)
            ->withDecorators(new NoOpMessageDecorator())
            ->withSubscribers(
                new NoOpMessageSubscriber(Reporter::DISPATCH_EVENT, 1),
                new NoOpMessageSubscriber(Reporter::FINALIZE_EVENT, -1),
            );

        $this->assertEquals(ReportCommand::class, $group->reporterConcrete());
        $this->assertEquals('reporter.command.default', $group->reporterId());
        $this->assertEquals(TrackMessage::class, $group->trackerId());
        $this->assertEquals('command', $group->handlerMethod());
        $this->assertEquals(ProducerStrategy::SYNC, $group->strategy());
        $this->assertEquals('message.producer.id', $group->producerId());
        $this->assertEquals(['connection' => 'redis', 'name' => 'transaction'], $group->queue());
        $this->assertCount(1, $group->decorators());
        $this->assertCount(2, $group->subscribers());
    }

    #[Test]
    public function it_raise_exception_when_reporter_class_is_not_a_valid_class_name(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Reporter concrete class reporter.command.default must be an instance of '.Reporter::class);

        $group = $this->group;

        $group->withReporterConcrete('reporter.command.default');
    }

    #[Test]
    public function it_raise_exception_when_producer_strategy_is_unknown_on_set(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Invalid message producer key: unknown_strategy');

        $group = $this->group;

        $group->withStrategy('unknown_strategy');
    }

    #[Test]
    public function it_raise_exception_when_producer_strategy_is_null_on_get(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Producer strategy can not be null');

        $group = $this->group;

        $group->strategy();
    }

    #[Test]
    public function it_can_be_serialized(): void
    {
        $group = $this->group;
        $group->withStrategy('sync');

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
