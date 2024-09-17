<?php

declare(strict_types=1);

namespace Infrastructure\Event\Adapter\Pdo;

use Application\Event\Mapper as EventMapper;
use Application\Messaging\Message;

class Mapper implements EventMapper
{
    public function map(Message $message, string $channel): array
    {
        return [
            ':name' => $message->getHeader('name'),
            ':source_id' => $message->getProperty('id'),
            ':channel' => $channel,
            ':user_id' => $message->getProperty('user_id') ?: null,
            ':correlation_id' => $message->getProperty('correlation_id') ?: null,
            ':aggregate_id' => $message->getHeader('aggregate_id'),
            ':aggregate_version' => $message->getHeader('aggregate_version'),
            ':data' => $message->getBody(),
            ':timestamp' => $message->getProperty('timestamp')
        ];
    }
}
