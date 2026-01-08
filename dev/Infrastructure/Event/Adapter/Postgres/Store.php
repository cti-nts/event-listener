<?php

declare(strict_types=1);

namespace Infrastructure\Event\Adapter\Postgres;

use Application\Event\Mapper;
use Application\Event\Store as EventStore;
use Application\Messaging\Message;
use PDO;
use RuntimeException;

class Store implements EventStore
{
    protected PDO $con;

    private const string UPDATE_EVENT_SQL = '
        INSERT INTO event(source_id, name, channel, correlation_id, user_id, aggregate_id, aggregate_version, data, "timestamp", received_at)
        VALUES (:source_id, :name, :channel, :correlation_id, :user_id, :aggregate_id, :aggregate_version, :data, :timestamp, NOW())
    ';

    private const string HAS_EVENT_SQL = 'SELECT id FROM event WHERE source_id = :source_id AND channel = :channel AND "timestamp" = :timestamp';

    public function __construct(protected readonly Mapper $mapper)
    {
        $host = getenv('STORE_DB_HOST') ?: throw new RuntimeException('STORE_DB_HOST environment variable is required');
        $port = getenv('DB_PORT') ?: '5432';
        $dbName = getenv('STORE_DB_NAME') ?: throw new RuntimeException('STORE_DB_NAME environment variable is required');
        $user = getenv('STORE_DB_USER') ?: throw new RuntimeException('STORE_DB_USER environment variable is required');
        $password = getenv('STORE_DB_PASSWORD') ?: throw new RuntimeException('STORE_DB_PASSWORD environment variable is required');
        $sslMode = getenv('STORE_DB_SSL_MODE') ?: '';

        $dsn = "pgsql:host=" . $host . ";port=" . $port . ";dbname=" . $dbName . ($sslMode ? ";sslmode=" . $sslMode : "");
        $this->con = new PDO($dsn, $user, $password);
    }

    public function add(Message $message, string $channel): void
    {
        $data = $this->mapper->map(message: $message, channel: $channel);
        $statement = $this->con->prepare(self::UPDATE_EVENT_SQL);
        $statement->execute($data);
    }

    public function hasEvent(int|string $sourceId, string $channel, string $timestamp): bool
    {
        $statement = $this->con->prepare(self::HAS_EVENT_SQL);
        $statement->execute([
            ':source_id' => $sourceId,
            ':channel' => $channel,
            ':timestamp' => $timestamp,
        ]);

        return !empty($statement->fetch());
    }
}
