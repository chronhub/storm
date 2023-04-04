<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Chronicler\TrackStream;
use Chronhub\Storm\Chronicler\TrackTransactionalStream;
use Chronhub\Storm\Chronicler\TransactionalEventDraft;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(TrackTransactionalStream::class)]
class TrackTransactionalStreamTest extends UnitTestCase
{
    #[Test]
    public function it_create_new_story(): void
    {
        $tracker = new TrackTransactionalStream();

        $this->assertInstanceOf(TrackStream::class, $tracker);

        $draft = $tracker->newStory('foo');

        $this->assertEquals(TransactionalEventDraft::class, $draft::class);
        $this->assertEquals('foo', $draft->currentEvent());
        $this->assertTrue($tracker->listeners()->isEmpty());
    }

    #[Test]
    public function it_disclose_transactional_story(): void
    {
        $tracker = new TrackTransactionalStream();

        $this->assertInstanceOf(TrackStream::class, $tracker);

        $draft = $tracker->newStory('foo');

        $this->assertEquals(TransactionalEventDraft::class, $draft::class);
        $this->assertEquals('foo', $draft->currentEvent());
        $this->assertTrue($tracker->listeners()->isEmpty());

        $tracker->watch('foo', function (TransactionalEventDraft $story) {
            $story->deferred(fn () => [$story->hasTransactionAlreadyStarted(), $story->hasTransactionNotStarted()]);
        });

        $tracker->disclose($draft);

        $this->assertSame([false, false], $draft->promise());
    }
}
