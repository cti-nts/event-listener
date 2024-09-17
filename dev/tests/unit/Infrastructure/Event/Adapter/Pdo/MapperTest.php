<?php

declare(strict_types=1);

namespace Infrastructure\Event\Adapter\Pdo;

use Application\Messaging\Message as ApplicationMessage;
use Enqueue\RdKafka\RdKafkaMessage;
use Infrastructure\Messaging\Adapter\EnqueueRdkafka\Message;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    public function testShouldMapTheMessageToDataForDbInsert(): void
    {
        $mapper = new Mapper();
        $this->assertEquals(
            $this->expectedEventData(),
            $mapper->map(
                message: $this->enqueueRdkafkaMessage(),
                channel: 'eventChannel'
            )
        );
    }

    private function enqueueRdkafkaMessage(): ApplicationMessage
    {
        return (new Message(delegate: new RdKafkaMessage()))
            ->withHeader(name: 'name', value: 'eventName')
            ->withHeader(name: 'aggregate_id', value: 12)
            ->withHeader(name: 'aggregate_version', value: 13)
            ->withProperty(name: 'timestamp', value: '2022-01-27 12:03:23 Z')
            ->withProperty(name: 'id', value: 27)
            ->withProperty(name: 'correlation_id', value: 'asd34fdf')
            ->withProperty(name: 'user_id', value: 'u00034')
            ->withBody(body: 'a test body');
    }

    private function expectedEventData()
    {
        return [
            ':name' => 'eventName',
            ':channel' => 'eventChannel',
            ':correlation_id' => 'asd34fdf',
            ':aggregate_id' => 12,
            ':aggregate_version' => 13,
            ':data' => 'a test body',
            ':timestamp' => '2022-01-27 12:03:23 Z',
            ':source_id' => 27,
            ':user_id' => 'u00034'
        ];
    }
}
