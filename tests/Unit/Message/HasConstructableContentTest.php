<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Message\HasConstructableContent;

final class HasConstructableContentTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_construct_with_empty_content(): void
    {
        $someContent = $this->newInstance();

        $this->assertEmpty($someContent->toContent());
        $this->assertEmpty($someContent->content);
    }

    /**
     * @test
     */
    public function it_construct_with_content(): void
    {
        $someContent = $this->newInstance(['name' => 'steph bug']);

        $this->assertEquals(['name' => 'steph bug'], $someContent->toContent());
        $this->assertEquals(['name' => 'steph bug'], $someContent->content);
    }

    /**
     * @test
     */
    public function it_instantiate_with_content(): void
    {
        $someContent = $this->newInstance([])::fromContent(['name' => 'steph bug']);

        $this->assertEquals(['name' => 'steph bug'], $someContent->toContent());
        $this->assertEquals(['name' => 'steph bug'], $someContent->content);
    }

    public function newInstance(array $content = []): object
    {
        return new class($content)
        {
            use HasConstructableContent;
        };
    }
}
