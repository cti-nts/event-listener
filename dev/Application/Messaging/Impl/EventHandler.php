<?php

declare(strict_types=1);

namespace Application\Messaging\Impl;

use Application\Event\Store;
use Application\Messaging\Filter;
use Application\Messaging\Handler;
use Application\Messaging\Message;
use Application\Messaging\Translator;
use Exception;

class EventHandler implements Handler
{
    public function __construct(
        protected readonly Store $store,
        protected readonly ?Filter $filter = null,
        protected readonly ?Translator $translator = null
    ) {
        echo "Initializing handler with filter " . ($filter ? $filter::class : '<NONE>') . " and translator " . ($translator ? $translator::class : '<NONE>') . "\n";
    }

    public function handle(Message $message, string $channel): void
    {
        if (empty($message->getProperties()['id'])) {
            throw new Exception('Invalid message with empty id property!');
        }

        $messageId = $message->getProperties()['id'];

        if ($this->store->hasEvent(
            sourceId: $messageId,
            channel: $channel,
            timestamp: $message->getProperties()['timestamp']
        )) {
            echo "Skipping duplicate message with id " . $messageId . " from channel " . $channel . "\n";
            return;
        }

        if ($this->filter !== null && !$this->filter->matches($message)) {
            echo "Skipping unmatched message with id " . $messageId . " from channel " . $channel . "\n";
            return;
        }

        if ($this->translator !== null) {
            $message = $this->translator->translate($message);
        }

        echo "Handling message with id " . $messageId . " from channel " . $channel . "\n";
        $this->store->add(message: $message, channel: $channel);
        echo "Successfully added message with id " . $messageId . " to store\n";
    }
}
