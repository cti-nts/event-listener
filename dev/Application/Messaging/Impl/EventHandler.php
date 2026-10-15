<?php

declare(strict_types=1);

namespace Application\Messaging\Impl;

use Application\Event\Store;
use Application\Messaging\Filter;
use Application\Messaging\Handler;
use Application\Messaging\Message;
use Application\Messaging\Translator;
use Exception;
use Throwable;

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
        $outcome = $this->handleBatch([$message], $channel)[0];
        if ($outcome['status'] === self::INVALID) {
            throw $outcome['exception'];
        }
    }

    public function handleBatch(array $messages, string $channel): array
    {
        $outcomes = [];
        $keys = [];       // index => [sourceId, channel, timestamp], for the messages that passed validation
        $seen = [];       // sourceId|timestamp => true, duplicates within the batch itself

        foreach ($messages as $index => $message) {
            try {
                [$messageId, $timestamp] = $this->identity($message);
            } catch (Exception $e) {
                $outcomes[$index] = ['status' => self::INVALID, 'exception' => $e];
                continue;
            }

            $seenKey = $messageId . '|' . $timestamp;
            if (isset($seen[$seenKey])) {
                echo "Skipping duplicate message with id " . $messageId . " from channel " . $channel . " (repeated in the batch)\n";
                $outcomes[$index] = ['status' => self::DUPLICATE];
                continue;
            }

            $seen[$seenKey] = true;
            $keys[$index] = [$messageId, $channel, $timestamp];
        }

        $existing = $this->store->hasEvents(array_values($keys));
        $position = 0;
        $toStore = [];
        foreach ($keys as $index => [$messageId]) {
            $isDuplicate = isset($existing[$position]);
            $position++;
            if ($isDuplicate) {
                echo "Skipping duplicate message with id " . $messageId . " from channel " . $channel . "\n";
                $outcomes[$index] = ['status' => self::DUPLICATE];
                continue;
            }

            $message = $messages[$index];
            try {
                if ($this->filter !== null && !$this->filter->matches($message)) {
                    echo "Skipping unmatched message with id " . $messageId . " from channel " . $channel . "\n";
                    $outcomes[$index] = ['status' => self::FILTERED];
                    continue;
                }

                if ($this->translator !== null) {
                    $message = $this->translator->translate($message);
                }
            } catch (Throwable $e) {
                $outcomes[$index] = ['status' => self::INVALID, 'exception' => $e];
                continue;
            }

            $toStore[$index] = $message;
        }

        if ($toStore !== []) {
            echo "Handling " . count($toStore) . " message(s) from channel " . $channel . "\n";
            $this->store->addMany(array_values($toStore), $channel);
            foreach (array_keys($toStore) as $index) {
                $outcomes[$index] = ['status' => self::STORED];
            }

            echo "Successfully added " . count($toStore) . " message(s) to store\n";
        }

        ksort($outcomes);

        return $outcomes;
    }

    /**
     * @return array{0: int|string, 1: string}
     */
    private function identity(Message $message): array
    {
        $properties = $message->getProperties();

        $messageId = $properties['id'] ?? null;
        if (empty($messageId)) {
            throw new Exception('Invalid message with empty id property!');
        }

        $timestamp = $properties['timestamp'] ?? null;
        if (empty($timestamp)) {
            throw new Exception('Invalid message with empty timestamp property!');
        }

        return [$messageId, (string)$timestamp];
    }
}
