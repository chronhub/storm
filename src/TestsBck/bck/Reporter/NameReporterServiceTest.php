<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\ReportCommand;
use Chronhub\Storm\Reporter\ReportEvent;
use Chronhub\Storm\Reporter\ReportQuery;
use Chronhub\Storm\Reporter\Subscribers\NameReporterService;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

#[CoversClass(NameReporterService::class)]
final class NameReporterServiceTest extends UnitTestCase
{
    public function testSubscriber(): void
    {
        $subscriber = new NameReporterService('reporter.service.id');

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::MESSAGE_FACTORY->value - 1
        );
    }

    #[DataProvider('provideReporterServiceId')]
    public function testSetHeader(string $serviceId): void
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

    public function testDoesNotSetHeaderIfAlreadyExists(): void
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
