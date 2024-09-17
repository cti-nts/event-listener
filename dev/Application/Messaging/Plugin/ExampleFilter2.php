<?php

declare(strict_types=1);

namespace Application\Messaging\Plugin;

use Application\Messaging\Filter;
use Application\Messaging\Message;

class ExampleFilter2 implements Filter
{
    public function __construct(protected readonly array $arg)
    {
    }

    public function matches(Message $message): bool
    {
        if ($message->getHeader('name') !== 'MySecondEventName') {
            return false;
        }

        return $message->getProperty('type') === 'MySecondEventType';
    }
}
