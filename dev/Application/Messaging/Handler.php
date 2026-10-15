<?php

declare(strict_types=1);

namespace Application\Messaging;

use Application\Event\Store;

interface Handler
{
    public const STORED = 'stored';

    public const DUPLICATE = 'duplicate';

    public const FILTERED = 'filtered';

    public const INVALID = 'invalid';

    public function __construct(Store $store, ?Filter $filter = null, ?Translator $translator = null);

    public function handle(Message $message, string $channel): void;

    /**
     * Handles the messages together: one lookup for duplicates and one transaction for the inserts.
     * A message that is invalid on its own (missing id or timestamp, a failing filter or translator)
     * is reported, not thrown, so the rest of the batch still goes through. A store failure is thrown
     * and nothing of the batch is stored.
     *
     * @param Message[] $messages
     * @return array<int, array{status: string, exception?: \Throwable}> One entry per message, same keys as $messages
     */
    public function handleBatch(array $messages, string $channel): array;
}
