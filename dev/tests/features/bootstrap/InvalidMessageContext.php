<?php

declare(strict_types=1);

use Assert\Assert;
use Behat\Behat\Context\Context;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\RdKafkaContext;

/**
 * Defines application features from the specific context.
 */
class InvalidMessageContext implements Context
{
    private static RdKafkaContext $kafkaContext;

    private string $channelWithInvalidFilter;

    /**
     * @BeforeSuite
     */
    public static function createKafkaContext(/* BeforeSuiteScope $scope */): void
    {
        self::$kafkaContext = (new RdKafkaConnectionFactory(
            [
                'global' => [
                    'metadata.broker.list' => getenv('MESSAGE_BROKER_HOST') . ':' . getenv('MESSAGE_BROKER_PORT'),
                    'group.id' => 'tester',
                ],
                'topic' => [
                    'auto.offset.reset' => 'earliest',
                    'enable.auto.commit' => 'true',
                    'auto.commit.interval.ms' => '10'
                ],
            ]
        ))->createContext();
    }

    /**
     * @BeforeScenario
     */
    public static function truncateEventTable(/* BeforeScenarioScope $scope */): void
    {
        $dsn = "pgsql:host=" . getenv('STORE_DB_HOST') . ";port=" . (getenv('DB_PORT') ?: '5432') . ";dbname=" . getenv('STORE_DB_NAME') . (getenv('STORE_DB_SSL_MODE') ? ";sslmode=" . getenv('STORE_DB_SSL_MODE') : "");
        $con = new \PDO($dsn, getenv('STORE_DB_USER'), getenv('STORE_DB_PASSWORD'));
        $stmt = $con->prepare('TRUNCATE TABLE event');
        $stmt->execute();
    }

    /**
     * @Given The invalid channel is set
     */
    public function theInvalidChannelIsSet(): void
    {
        Assert::that(getenv('INVALID_CHANNEL'))->notEmpty();
    }

    /**
     * @When listener encounters an invalid message
     */
    public function listenerEncountersAnInvalidMessage(): void
    {
        Assert::that(getenv('EVENT_CHANNELS'))->contains('InvalidFilter');

        $this->channelWithInvalidFilter = explode(
            ';',
            trim(
                array_values(
                    array_filter(
                        explode("\n", getenv('EVENT_CHANNELS')),
                        fn ($row) => str_contains($row, 'InvalidFilter')
                    )
                )[0]
            )
        )[0];

        Assert::that($this->channelWithInvalidFilter)->notEmpty();

        var_dump($this->channelWithInvalidFilter);

        $topic = self::$kafkaContext->createTopic($this->channelWithInvalidFilter);
        $producer = self::$kafkaContext->createProducer();
        $producer->send($topic, self::$kafkaContext->createMessage(
            'Invalid message',
            [],
            ['name' => 'invalid']
        ));
    }

    /**
     * @Then it should republish it on invalid channel
     */
    public function itShouldRepublishItOnInvalidChannel(): void
    {
        $topic = self::$kafkaContext->createTopic(getenv('INVALID_CHANNEL'));
        $consumer = self::$kafkaContext->createConsumer($topic);
        $message = $consumer->receive(60000);

        if ($message !== null) {
            while ($res = $consumer->receive(1000)) {
                $message = $res;
            }
        }

        $consumer->acknowledge($message);

        Assert::that($message)->notNull();
        Assert::that($message->getProperty('source'))->eq($this->channelWithInvalidFilter);
        Assert::that($message->getProperty('invalidBy'))->eq(getenv('MESSAGE_CONSUMER_GROUP'));
        Assert::that($message->getProperty('invalidAt'))->notEmpty();
        Assert::that($message->getProperty('exception'))->notEmpty();
    }
}
