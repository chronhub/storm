<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use stdClass;
use Generator;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Reporter\ReportEvent;
use Chronhub\Storm\Reporter\ReportQuery;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Reporter\ReportCommand;
use Chronhub\Storm\Contracts\Message\Header;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\Subscribers\NameReporterService;

final class NameReporterServiceTest extends UnitTestCase
{
    #[Test]
    public function it_test_subscriber(): void
    {
        $subscriber = new NameReporterService('reporter.service.id');

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::MESSAGE_FACTORY->value - 1
        );
    }

    #[DataProvider('provideReporterServiceId')]
    #[Test]
    public function it_mark_reporter_service_name_in_message_header(string $serviceId): void
    {
        $tracker = new TrackMessage();

        $subscriber = new NameReporterService($serviceId);
        $subscriber->attachToReporter($tracker);

        $message = new Message(new stdClass());

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $tracker->disclose($story);

        $this->assertEquals($serviceId, $story->message()->header(Header::REPORTER_ID));
    }

    #[Test]
    public function it_does_not_mark_reporter_name_header_if_already_exists(): void
    {
        $tracker = new TrackMessage();

        $subscriber = new NameReporterService('reporter.service_id');
        $subscriber->attachToReporter($tracker);

        $message = new Message(new stdClass(), [Header::REPORTER_ID => 'my_service_id']);

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);
        $story->withMessage($message);

        $tracker->disclose($story);

        $this->assertEquals('my_service_id', $story->message()->header(Header::REPORTER_ID));
    }

    public static function provideReporterServiceId(): Generator
    {
        yield ['reporter.service_id'];
        yield [ReportCommand::class];
        yield [ReportEvent::class];
        yield [ReportQuery::class];
    }
}
