<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Routing\EventGroup;
use Chronhub\Storm\Routing\QueryGroup;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CommandGroup;
use Chronhub\Storm\Routing\RoutingRegistrar;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;

#[CoversClass(RoutingRegistrar::class)]
final class RoutingRegistrarTest extends UnitTestCase
{
    private RoutingRegistrar $registrar;

    protected function setUp(): void
    {
        $this->registrar = new RoutingRegistrar(new AliasFromClassName());
    }

    #[Test]
    public function it_can_be_constructed_with_empty_groups(): void
    {
        $this->assertTrue($this->registrar->all()->isEmpty());

        $this->assertNotSame($this->registrar->all(), $this->registrar->all());
    }

    #[Test]
    public function it_create_new_instance_of_group(): void
    {
        $commandGroup = $this->registrar->make(DomainType::COMMAND, 'default');
        $this->assertInstanceOf(CommandGroup::class, $commandGroup);

        $eventGroup = $this->registrar->make(DomainType::EVENT, 'default');
        $this->assertInstanceOf(EventGroup::class, $eventGroup);

        $queryGroup = $this->registrar->make(DomainType::QUERY, 'default');
        $this->assertInstanceOf(QueryGroup::class, $queryGroup);

        $this->assertNotSame($this->registrar->all(), $this->registrar->all());
        $this->assertCount(3, $this->registrar->all());
    }

    #[Test]
    public function it_create_new_instance_of_group_2(): void
    {
        $commandGroup = $this->registrar->makeCommand('default');
        $this->assertInstanceOf(CommandGroup::class, $commandGroup);

        $eventGroup = $this->registrar->makeEvent('default');
        $this->assertInstanceOf(EventGroup::class, $eventGroup);

        $queryGroup = $this->registrar->makeQuery('default');
        $this->assertInstanceOf(QueryGroup::class, $queryGroup);

        $this->assertNotSame($this->registrar->all(), $this->registrar->all());
        $this->assertCount(3, $this->registrar->all());
    }

    #[Test]
    public function it_add_sub_group(): void
    {
        $defaultGroup = $this->registrar->make(DomainType::COMMAND, 'default');
        $anotherGroup = $this->registrar->make(DomainType::COMMAND, 'another');

        $this->assertNotSame($defaultGroup, $anotherGroup);

        $this->assertCount(1, $this->registrar->all());
    }

    #[Test]
    public function it_access_group_with_type_and_name(): void
    {
        $this->assertNull($this->registrar->get(DomainType::COMMAND, 'default'));

        $group = $this->registrar->make(DomainType::COMMAND, 'default');

        $this->assertEquals($group, $this->registrar->get(DomainType::COMMAND, 'default'));
    }

    #[Test]
    public function it_raise_exception_when_name_already_exists_in_group(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('command domain already exists with name default');

        $this->registrar->make(DomainType::COMMAND, 'default');
        $this->registrar->make(DomainType::COMMAND, 'default');
    }

    #[Test]
    public function it_serialize_all_groups(): void
    {
        $this->registrar->make(DomainType::COMMAND, 'default_command_group');
        $this->registrar->make(DomainType::COMMAND, 'another_command_group');
        $this->registrar->make(DomainType::EVENT, 'default_event_group');
        $this->registrar->make(DomainType::QUERY, 'default_query_group');

        $groups = $this->registrar->all()->jsonSerialize();

        $this->assertCount(2, $groups['command']);
        $this->assertArrayHasKey('default_command_group', $groups['command']);
        $this->assertArrayHasKey('another_command_group', $groups['command']);

        $this->assertCount(1, $groups['event']);
        $this->assertArrayHasKey('default_event_group', $groups['event']);

        $this->assertCount(1, $groups['query']);
        $this->assertArrayHasKey('default_query_group', $groups['query']);
    }
}
