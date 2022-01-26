<?php

namespace Application\Messaging\Plugin;

use Application\Messaging\Filter;
use Application\Messaging\Message;

class ExampleFilter2 implements Filter
{
    public function matches(Message $message): bool
    {
        return 
            $message->getHeader('name') == 'MySecondEventName'
            && $message->getPropery('type') == 'MySecondEventType';
    }
}
