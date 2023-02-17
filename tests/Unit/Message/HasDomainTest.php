<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\DomainQuery;
use Chronhub\Storm\Reporter\DomainCommand;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\Double\SomeQuery;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Contracts\Reporter\Reporting;

final class HasDomainTest extends UnitTestCase
{
    /**
     * @test
     *
     * @dataProvider provideDomain
     */
    public function it_test_domain_content(Reporting $domain): void
    {
        $this->assertEmpty($domain->headers());

        $this->assertEquals(['name' => 'steph bug'], $domain->toContent());

        if ($domain instanceof DomainCommand) {
            $this->assertEquals(DomainType::COMMAND, $domain->type());
        }

        if ($domain instanceof DomainEvent) {
            $this->assertEquals(DomainType::EVENT, $domain->type());
        }

        if ($domain instanceof DomainQuery) {
            $this->assertEquals(DomainType::QUERY, $domain->type());
        }
    }

    /**
     * @test
     *
     * @dataProvider provideDomain
     */
    public function it_add_header_and_return_new_instance_of_domain(Reporting $domain): void
    {
        $this->assertEmpty($domain->headers());

        $this->assertTrue($domain->hasNot('some'));
        $this->assertNull($domain->header('unknown'));

        $cloned = $domain->withHeader('name', 'steph bug');

        $this->assertNotSame($domain, $cloned);

        $this->assertTrue($cloned->has('name'));
        $this->assertNull($cloned->header('unknown header'));
        $this->assertEquals('steph bug', $cloned->header('name'));
        $this->assertEquals(['name' => 'steph bug'], $cloned->headers());
        $this->assertEquals(['name' => 'steph bug'], $cloned->toContent());
    }

    /**
     * @test
     *
     * @dataProvider provideDomain
     */
    public function it_add_headers_and_return_new_domain_instance(Reporting $domain): void
    {
        $this->assertEmpty($domain->headers());

        $this->assertTrue($domain->hasNot('name'));
        $this->assertNull($domain->header('unknown header'));

        $cloned = $domain->withHeaders(['name' => 'steph bug']);

        $this->assertNotEquals($domain, $cloned);

        $this->assertTrue($cloned->has('name'));
        $this->assertNull($cloned->header('unknown'));
        $this->assertEquals('steph bug', $cloned->header('name'));
        $this->assertEquals(['name' => 'steph bug'], $cloned->headers());
        $this->assertEquals(['name' => 'steph bug'], $cloned->toContent());
    }

    public function provideDomain(): Generator
    {
        $content = ['name' => 'steph bug'];

        yield [SomeCommand::fromContent($content)];
        yield [SomeEvent::fromContent($content)];
        yield [SomeQuery::fromContent($content)];
    }
}
