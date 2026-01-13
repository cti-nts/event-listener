<?php

declare(strict_types=1);

namespace Infrastructure\Event\Adapter\Pdo;

use Application\Event\Mapper as EventMapper;
use Application\Messaging\Message;

class Mapper implements EventMapper
{
    /**
     * @return array<string, mixed>
     */
    public function map(Message $message, string $channel): array
    {
        $props = [
            'id' => $message->getProperty('id'),
            'user_id' => $message->getProperty('user_id') ?: null,
            'correlation_id' => $message->getProperty('correlation_id') ?: null,
            'timestamp' => $message->getProperty('timestamp'),
        ];

        $headers = [
            'name' => $message->getHeader('name'),
            'aggregate_id' => $message->getHeader('aggregate_id'),
            'aggregate_version' => $message->getHeader('aggregate_version'),
        ];

        return [
            ':name' => $headers['name'],
            ':source_id' => $props['id'],
            ':channel' => $channel,
            ':user_id' => $props['user_id'],
            ':correlation_id' => $props['correlation_id'],
            ':aggregate_id' => $headers['aggregate_id'],
            ':aggregate_version' => $headers['aggregate_version'],
            ':data' => $message->getBody(),
            ':timestamp' => $props['timestamp'],
        ];
    }
}
