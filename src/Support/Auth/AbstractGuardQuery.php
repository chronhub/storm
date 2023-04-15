<?php

declare(strict_types=1);

namespace Chronhub\Storm\Support\Auth;

use Chronhub\Storm\Contracts\Auth\AuthorizeMessage;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Reporter\DetachMessageListener;
use function sprintf;

abstract class AbstractGuardQuery implements MessageSubscriber
{
    use DetachMessageListener;

    public function __construct(
        protected readonly AuthorizeMessage $authorization,
        protected readonly MessageAlias $messageAlias
    ) {
    }

    protected function authorizeQuery(MessageStory $story, mixed $context = null): void
    {
        $message = $story->message();

        $messageName = $this->messageAlias->classToAlias($message->header(Header::EVENT_TYPE));

        if ($this->authorization->isNotGranted($messageName, $message, $context)) {
            $story->stop(true);

            throw new UnauthorizedException(sprintf('Unauthorized query %s', $messageName));
        }
    }
}
