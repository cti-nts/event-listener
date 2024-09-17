<?php

declare(strict_types=1);

namespace Application\Event;

use Application\Messaging\Message;

interface Store
{
    public function __construct(Mapper $mapper);

    public function add(Message $message, string $channel): void;

    public function hasEvent(int $sourceId, string $channel, string $timestamp): bool;
}
