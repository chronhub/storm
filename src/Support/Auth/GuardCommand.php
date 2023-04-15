<?php

declare(strict_types=1);

namespace Chronhub\Storm\Support\Auth;

use Chronhub\Storm\Contracts\Auth\AuthorizeMessage;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\DetachMessageListener;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use function sprintf;

final class GuardCommand implements MessageSubscriber
{
    use DetachMessageListener;

    public function __construct(
       private readonly AuthorizeMessage $authorization,
       private readonly MessageAlias $messageAlias
    ) {
    }

    public function attachToReporter(MessageTracker $tracker): void
    {
        $this->messageListeners[] = $tracker->onDispatch(function (MessageStory $story): void {
            $message = $story->message();

            $messageName = $this->messageAlias->classToAlias($message->header(Header::EVENT_TYPE));

            // note:
            // we do not restrict command authorization to DomainCommand
            // as dev can use command bus to dispatch and authorize naked command,
            // we just pass the current class as context to the authorization service
            if ($this->authorization->isNotGranted($messageName, $message, GuardCommand::class)) {
                $story->stop(true);

                throw new UnauthorizedException(
                    sprintf('Unauthorized command %s', $messageName)
                );
            }

        }, OnDispatchPriority::GUARD_COMMAND->value);
    }
}
