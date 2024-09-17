<?php

declare(strict_types=1);

use Assert\Assert;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Call\BeforeScenario;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\RdKafkaContext;

/**
 * Defines application features from the specific context.
 */
class ValidMessageContext implements Context
{
    private static RdKafkaContext $kafkaContext;

    private string $channelWithNoFilterNoTranslator;

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
        $stmt = $con->prepare('TRUNCATE TABLE "event"');
        $stmt->execute();
    }

    /**
     * @Given The channel is set
     */
    public function theChannelIsSet(): void
    {
        $this->channelWithNoFilterNoTranslator = trim(
            array_values(
                array_filter(
                    explode("\n", getenv('EVENT_CHANNELS')),
                    fn ($row) => $row !== '' && $row !== '0' && !str_contains($row, ';')
                )
            )[0]
        );
        echo $this->channelWithNoFilterNoTranslator;
    }

    /**
     * @When listener encounters a valid message
     */
    public function listenerEncountersAValidMessage(): void
    {
        $topic = self::$kafkaContext->createTopic($this->channelWithNoFilterNoTranslator);
        $producer = self::$kafkaContext->createProducer();
        $producer->send($topic, self::$kafkaContext->createMessage(
            '{"attr1": "val1", "attr2": "val2"}',
            [
                'id' => 123,
                'timestamp' => '2022-01-28 12:23:56'
            ],
            [
                'name' => 'eventName',
                'aggregate_id' => 23,
                'aggregate_version' => 7
            ]
        ));
    }

    /**
     * @When listener encounters the same valid message
     */
    public function listenerEncountersTheSameValidMessage(): void
    {
        $this->listenerEncountersAValidMessage();
    }

    /**
     * @When listener encounters a valid message with same id from different channel
     */
    public function listenerEncountersAValidMessageWithSameIdFromDifferentChannel(): void
    {
        $topic = self::$kafkaContext->createTopic('fourth-channel');
        $producer = self::$kafkaContext->createProducer();
        $producer->send($topic, self::$kafkaContext->createMessage(
            '{"attr1": "val1", "attr2": "val2"}',
            [
                'id' => 123,
                'timestamp' => '2022-01-28 12:23:56'
            ],
            [
                'name' => 'eventName',
                'aggregate_id' => 23,
                'aggregate_version' => 7
            ]
        ));
    }

    /**
     * @Then it should insert it in db
     */
    public function itShouldInsertItInDb(string $channel = null): void
    {
        if (!$channel) {
            $channel = $this->channelWithNoFilterNoTranslator;
        }

        $event = null;
        $count = 0;
        while (!$event && $count < 60) {
            $dsn = "pgsql:host=" . getenv('STORE_DB_HOST') . ";port=" . (getenv('DB_PORT') ?: '5432') . ";dbname=" . getenv('STORE_DB_NAME') . (getenv('STORE_DB_SSL_MODE') ? ";sslmode=" . getenv('STORE_DB_SSL_MODE') : "");
            $con = new \PDO($dsn, getenv('STORE_DB_USER'), getenv('STORE_DB_PASSWORD'));
            $stmt = $con->prepare('SELECT * FROM event WHERE source_id = :eventId AND channel = :channel');
            $stmt->execute([':eventId' => 123, ':channel' => $channel]);
            $event = $stmt->fetch();
            usleep(500_000);
            $count++;
        }

        Assert::that($event)->notEmpty();
        Assert::that($event['source_id'])->eq(123);
        Assert::that($event['correlation_id'])->eq(null);
        Assert::that($event['timestamp'])->eq('2022-01-28 12:23:56');
        Assert::that($event['name'])->eq('eventName');
        Assert::that($event['aggregate_id'])->eq(23);
        Assert::that($event['aggregate_version'])->eq(7);
        Assert::that($event['channel'])->eq($channel);
    }

    /**
     * @Then it should insert it in db only once
     */
    public function itShouldInsertItInDbOnlyOnce(): void
    {
        $this->itShouldInsertItInDb();

        $dsn = "pgsql:host=" . getenv('STORE_DB_HOST') . ";port=" . (getenv('DB_PORT') ?: '5432') . ";dbname=" . getenv('STORE_DB_NAME') . (getenv('STORE_DB_SSL_MODE') ? ";sslmode=" . getenv('STORE_DB_SSL_MODE') : "");
        $con = new \PDO($dsn, getenv('STORE_DB_USER'), getenv('STORE_DB_PASSWORD'));
        $stmt = $con->prepare('SELECT count(*) FROM event');
        $stmt->execute();

        $count = $stmt->fetch()['count'];
        Assert::that($count)->eq(1);
    }

    /**
     * @Then it should insert both in db
     */
    public function itShouldInsertBothInDb(): void
    {
        $this->itShouldInsertItInDb();
        $this->itShouldInsertItInDb('fourth-channel');
    }

    /**
     * @When listener encounters a valid message with correlation id
     */
    public function listenerEncountersAValidMessageWithCorrelationId(): void
    {
        $topic = self::$kafkaContext->createTopic($this->channelWithNoFilterNoTranslator);
        $producer = self::$kafkaContext->createProducer();
        $producer->send($topic, self::$kafkaContext->createMessage(
            '{"attr1": "val1", "attr2": "val2"}',
            [
                'id' => 123,
                'correlation_id' => '456',
                'timestamp' => '2022-01-28 12:23:56'
            ],
            [
                'name' => 'eventName',
                'aggregate_id' => 23,
                'aggregate_version' => 7
            ]
        ));
    }

    /**
     * @When listener encounters a valid message with user id
     */
    public function listenerEncountersAValidMessageWithUserId(): void
    {
        $topic = self::$kafkaContext->createTopic($this->channelWithNoFilterNoTranslator);
        $producer = self::$kafkaContext->createProducer();
        $producer->send($topic, self::$kafkaContext->createMessage(
            '{"attr1": "val1", "attr2": "val2"}',
            [
                'id' => 123,
                'correlation_id' => '456',
                'user_id' => 'testid',
                'timestamp' => '2022-01-28 12:23:56'
            ],
            [
                'name' => 'eventName',
                'aggregate_id' => 23,
                'aggregate_version' => 7
            ]
        ));
    }

    /**
     * @Then it should insert it in db with correlation id
     */
    public function itShouldInsertItInDbWithCorrelationId(): void
    {
        $event = null;
        $count = 0;
        while (!$event && $count < 60) {
            $dsn = "pgsql:host=" . getenv('STORE_DB_HOST') . ";port=" . (getenv('DB_PORT') ?: '5432') . ";dbname=" . getenv('STORE_DB_NAME') . (getenv('STORE_DB_SSL_MODE') ? ";sslmode=" . getenv('STORE_DB_SSL_MODE') : "");
            $con = new \PDO($dsn, getenv('STORE_DB_USER'), getenv('STORE_DB_PASSWORD'));
            $stmt = $con->prepare('SELECT * FROM event WHERE correlation_id = :correlation_id');
            $stmt->execute([':correlation_id' => '456']);
            $event = $stmt->fetch();
            usleep(500_000);
            $count++;
        }

        Assert::that($event)->notEmpty();
        Assert::that($event['correlation_id'])->eq('456');
        Assert::that($event['timestamp'])->eq('2022-01-28 12:23:56');
        Assert::that($event['name'])->eq('eventName');
        Assert::that($event['aggregate_id'])->eq(23);
        Assert::that($event['aggregate_version'])->eq(7);
        Assert::that($event['channel'])->eq($this->channelWithNoFilterNoTranslator);
    }

    /**
     * @Then it should insert it in db with user id
     */
    public function itShouldInsertItInDbWithUserId(): void
    {
        $event = null;
        $count = 0;
        while (!$event && $count < 60) {
            $dsn = "pgsql:host=" . getenv('STORE_DB_HOST') . ";port=" . (getenv('DB_PORT') ?: '5432') . ";dbname=" . getenv('STORE_DB_NAME') . (getenv('STORE_DB_SSL_MODE') ? ";sslmode=" . getenv('STORE_DB_SSL_MODE') : "");
            $con = new \PDO($dsn, getenv('STORE_DB_USER'), getenv('STORE_DB_PASSWORD'));
            $stmt = $con->prepare('SELECT * FROM event WHERE user_id = :user_id');
            $stmt->execute([':user_id' => 'testid']);
            $event = $stmt->fetch();
            usleep(500_000);
            $count++;
        }

        Assert::that($event)->notEmpty();
        Assert::that($event['user_id'])->eq('testid');
        Assert::that($event['timestamp'])->eq('2022-01-28 12:23:56');
        Assert::that($event['name'])->eq('eventName');
        Assert::that($event['aggregate_id'])->eq(23);
        Assert::that($event['aggregate_version'])->eq(7);
        Assert::that($event['channel'])->eq($this->channelWithNoFilterNoTranslator);
    }
}
