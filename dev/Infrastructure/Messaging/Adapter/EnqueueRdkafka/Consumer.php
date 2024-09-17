<?php

declare(strict_types=1);

namespace Infrastructure\Messaging\Adapter\EnqueueRdkafka;

use Application\Messaging\Consumer as ApplicationConsumer;
use Application\Messaging\Handler;
use DateTime;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\RdKafkaConsumer;
use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\RdKafka\RdKafkaTopic;
use Exception;
use PDOException;

class Consumer implements ApplicationConsumer
{
    protected RdKafkaContext $context;

    protected RdKafkaTopic $topic;

    protected RdKafkaConsumer $delegate;

    protected RdKafkaTopic $invalidTopic;

    public function __construct(
        protected readonly array $config,
        protected readonly string $channel,
        protected readonly Handler $handler,
        protected readonly ?string $invalidChannel = null
    ) {
        $this->context = (new RdKafkaConnectionFactory($config))->createContext();
        $this->topic = $this->context->createTopic($channel);
        $this->delegate = $this->context->createConsumer($this->topic);

        if ($invalidChannel) {
            $this->invalidTopic = $this->context->createTopic($invalidChannel);
        }
    }

    public function start(): void
    {
        $topicName = $this->delegate->getQueue()->getTopicName();

        // @phpstan-ignore while.alwaysTrue
        while (true) {
            echo "Listening on channel " . $topicName . "...\n";

            try {
                $message = $this->delegate->receive();
                if ($message !== null) {
                    $this->handler->handle(message: new Message($message), channel: $this->channel);
                    $this->delegate->acknowledge($message);
                }
            } catch (Exception $e) {
                echo "RECEIVE ERROR!!! Channel: " . $topicName . ", Error: " . $e::class . ", code: " . $e->getCode() . ", message: " . $e->getMessage() . "\n";

                if ($e::class === PDOException::class) {
                    echo "DB ERROR!!! I will NOT REJECT the message. I will re-throw it and try again to consume it.\n";
                    throw $e;
                }

                if (empty($message)) {
                    continue;
                }

                echo "REJECTING MESSAGE:\n" . print_r($message, true);
                $this->delegate->reject($message);

                if (empty($this->invalidTopic)) {
                    continue;
                }

                echo "Sending message to invalid channel...\n";

                $invalidProducer = $this->context->createProducer();

                $message->setProperty('source', $topicName);
                $message->setProperty('invalidBy', $this->config['global']['group.id']);
                $message->setProperty('invalidAt', (new DateTime())->format('Y-m-d H:i:s'));
                $message->setProperty('exception', [
                    'class' => $e::class,
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $invalidProducer->send($this->invalidTopic, $message);
            }
        }
    }
}
