<?php

declare(strict_types=1);

namespace Application\Event;

use Application\Messaging\Message;

interface Store
{
    public function __construct(Mapper $mapper);

    public function add(Message $message, string $channel): void;

    public function hasEvent(int|string $sourceId, string $channel, string $timestamp): bool;

    /**
     * Which of the given (sourceId, channel, timestamp) keys already exist, in one query.
     *
     * @param array<int, array{0: int|string, 1: string, 2: string}> $keys
     * @return array<int, bool> The indexes of $keys that exist, as keys of the returned array
     */
    public function hasEvents(array $keys): array;

    /**
     * Stores the messages in one transaction: all of them or none.
     *
     * @param Message[] $messages
     */
    public function addMany(array $messages, string $channel): void;
}
