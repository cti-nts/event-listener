<?php

declare(strict_types=1);

namespace Infrastructure\Messaging\Adapter\EnqueueRdkafka;

use Application\Messaging\Consumer as ApplicationConsumer;
use Application\Messaging\Handler;
use DateTimeImmutable;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\RdKafkaConsumer;
use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\RdKafka\RdKafkaMessage;
use Enqueue\RdKafka\RdKafkaTopic;
use Exception;
use PDOException;
use Throwable;

class Consumer implements ApplicationConsumer
{
    /** Messages handled together: one duplicate lookup, one insert transaction, instead of two round trips each */
    public const DEFAULT_BATCH_SIZE = 100;

    /** How long to wait for the next message once a batch has started, before handling what is there */
    public const DEFAULT_BATCH_WAIT_MS = 50;

    protected RdKafkaContext $context;

    protected RdKafkaTopic $topic;

    protected RdKafkaConsumer $delegate;

    protected ?RdKafkaTopic $invalidTopic = null;

    protected int $batchSize;

    protected int $batchWaitMs;

    public function __construct(
        protected readonly array $config,
        protected readonly string $channel,
        protected readonly Handler $handler,
        protected readonly ?string $invalidChannel = null,
        array $batch = []
    ) {
        $this->context = (new RdKafkaConnectionFactory($config))->createContext();
        $this->topic = $this->context->createTopic($channel);
        $this->delegate = $this->context->createConsumer($this->topic);
        if ($invalidChannel) {
            $this->invalidTopic = $this->context->createTopic($invalidChannel);
        }

        $this->batchSize = max(1, (int)($batch['size'] ?? self::DEFAULT_BATCH_SIZE));
        $this->batchWaitMs = max(1, (int)($batch['waitMs'] ?? self::DEFAULT_BATCH_WAIT_MS));
    }

    public function start(): void
    {
        $topicName = $this->delegate->getQueue()->getTopicName();
        echo "Listening on channel " . $topicName . " (batches of up to " . $this->batchSize . ", " . $this->batchWaitMs . "ms wait)...\n";

        // @phpstan-ignore while.alwaysTrue
        while (true) {
            $batch = [];
            try {
                $batch = $this->receiveBatch();
                if ($batch === []) {
                    continue;
                }

                $outcomes = $this->handler->handleBatch(
                    messages: array_map(static fn (RdKafkaMessage $m): Message => new Message($m), $batch),
                    channel: $this->channel
                );

                foreach ($batch as $index => $message) {
                    $outcome = $outcomes[$index] ?? ['status' => Handler::STORED];
                    if ($outcome['status'] === Handler::INVALID) {
                        $this->sendToInvalid($message, $outcome['exception'], $topicName);
                        continue;
                    }

                    // Commits the offset (async, so no round trip per message); the store has the message
                    $this->delegate->acknowledge($message);
                }
            } catch (Exception $e) {
                echo "RECEIVE ERROR!!! Channel: " . $topicName . ", Error: " . $e::class . ", code: " . $e->getCode() . ", message: " . $e->getMessage() . "\n";
                if ($e::class === PDOException::class) {
                    echo "DB ERROR!!! I will NOT REJECT the message(s). I will re-throw it and try again to consume them.\n";
                    throw $e;
                }

                if ($batch === []) {
                    continue;
                }

                // The failure is not attributable to one message of the batch: nothing is acknowledged, the
                // batch is consumed again after the restart and the duplicate check keeps it idempotent
                echo "BATCH ERROR!!! " . count($batch) . " message(s) left unacknowledged for retry.\n";
                throw $e;
            }
        }
    }

    /**
     * Blocks for the first message, then takes whatever else arrives within the batch wait, up to the batch size.
     *
     * @return RdKafkaMessage[]
     */
    protected function receiveBatch(): array
    {
        $batch = [];
        $message = $this->delegate->receive();
        if ($message === null) {
            return [];
        }

        $batch[] = $message;

        while (count($batch) < $this->batchSize) {
            $message = $this->delegate->receive($this->batchWaitMs);
            if ($message === null) {
                break;
            }

            $batch[] = $message;
        }

        return $batch;
    }

    protected function sendToInvalid(RdKafkaMessage $message, Throwable $e, string $topicName): void
    {
        echo "REJECTING MESSAGE:\n" . print_r($message, true);
        $this->delegate->reject($message);
        if (!$this->invalidTopic instanceof RdKafkaTopic) {
            return;
        }

        echo "Sending message to invalid channel...\n";
        $invalidProducer = $this->context->createProducer();
        $message->setProperty('source', $topicName);
        $message->setProperty('invalidBy', $this->config['global']['group.id']);
        $message->setProperty('invalidAt', (new DateTimeImmutable())->format('Y-m-d H:i:s.u'));
        $message->setProperty('exception', [
            'class' => $e::class,
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $invalidProducer->send($this->invalidTopic, $message);
    }
}
