<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Message\NoOpMessageDecorator;
use Chronhub\Storm\Producer\ProducerStrategy;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Reporter\ReportCommand;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Group;
use Chronhub\Storm\Routing\Rules\RequireAtLeastOneRoute;
use Chronhub\Storm\Routing\Rules\RequireOneHandlerRule;
use Chronhub\Storm\Tests\Stubs\Double\NoOpMessageSubscriber;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Group::class)]
final class GroupTest extends UnitTestCase
{
    private CollectRoutes $routes;

    protected function setUp(): void
    {
        $this->routes = new CollectRoutes(new AliasFromClassName());
    }

    #[DataProvider('provideDomainType')]
    public function testGroupInstance(DomainType $domainType): void
    {
        $group = new Group($domainType, 'default', $this->routes);

        $this->assertNull($group->reporterConcrete());
        $this->assertNull($group->reporterId());
        $this->assertNull($group->trackerId());
        $this->assertNull($group->handlerMethod());
        $this->assertNull($group->producerId());
        $this->assertNull($group->queue());
        $this->assertEmpty($group->decorators());
        $this->assertEmpty($group->subscribers());
        $this->assertEmpty($group->rules());
    }

    #[DataProvider('provideDomainType')]
    public function testPropertiesSetter(DomainType $domainType): void
    {
        $group = new Group($domainType, 'default', $this->routes);

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

    #[DataProvider('provideDomainType')]
    public function testExceptionRaisedWhenReporterConcreteIsNotInstanceOfReporting(DomainType $domainType): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Reporter concrete class reporter.command.default must be an instance of '.Reporter::class);

        $group = new Group($domainType, 'default', $this->routes);
        $group->withReporterConcrete('reporter.command.default');
    }

    #[DataProvider('provideDomainType')]
    public function testExceptionRaisedWhenProducerStrategyIsInvalid(DomainType $domainType): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Invalid message producer strategy unknown_strategy');

        $group = new Group($domainType, 'default', $this->routes);
        $group->withStrategy('unknown_strategy');
    }

    #[DataProvider('provideDomainType')]
    public function testExceptionRaisedWhenProducerStrategyIsNotSet(DomainType $domainType): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Producer strategy can not be null');

        $group = new Group($domainType, 'default', $this->routes);
        $group->strategy();
    }

    #[DataProvider('provideDomainType')]
    public function testAddRules(DomainType $domainType): void
    {
        $group = new Group($domainType, 'default', $this->routes);

        $group->addRule(new RequireOneHandlerRule());

        $this->assertCount(1, $group->rules());

        $group->addRule(new RequireAtLeastOneRoute());

        $this->assertCount(2, $group->rules());
    }

    #[DataProvider('provideDomainType')]
    public function testItSerializeGroup(DomainType $domainType): void
    {
        $group = new Group($domainType, 'default', $this->routes);

        $group->withStrategy('sync');

        $this->assertEquals([
            $domainType->value => [
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

    public static function provideDomainType(): Generator
    {
        yield [DomainType::COMMAND];
        yield [DomainType::QUERY];
        yield [DomainType::EVENT];
    }
}
