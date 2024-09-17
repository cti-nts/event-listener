<?php

declare(strict_types=1);

namespace Application\Event;

use Application\Messaging\Message;

interface Mapper
{
    public function map(Message $message, string $channel): array;
}
