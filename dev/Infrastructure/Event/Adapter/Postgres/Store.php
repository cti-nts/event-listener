<?php

declare(strict_types=1);

namespace Infrastructure\Event\Adapter\Postgres;

use Application\Event\Mapper;
use Application\Event\Store as EventStore;
use Application\Messaging\Message;
use DateTimeImmutable;
use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

class Store implements EventStore
{
    protected PDO $con;

    /** The columns of a row, in the order the mapper's keys are read for a multi-row insert */
    private const array COLUMNS = ['source_id', 'name', 'channel', 'correlation_id', 'user_id', 'aggregate_id', 'aggregate_version', 'data', 'timestamp'];

    protected const UPDATE_EVENT_SQL = '
        INSERT INTO event(source_id, name, channel, correlation_id, user_id, aggregate_id, aggregate_version, data, "timestamp", received_at)
        VALUES (:source_id, :name, :channel, :correlation_id, :user_id, :aggregate_id, :aggregate_version, :data, :timestamp, NOW())
    ';

    protected const HAS_EVENT_SQL = 'SELECT id FROM event WHERE source_id = :source_id AND channel = :channel AND "timestamp" = :timestamp';

    private ?PDOStatement $addStatement = null;

    private ?PDOStatement $hasEventStatement = null;

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
        // Prepared once per process: the statement is the same for every message
        $this->addStatement ??= $this->con->prepare(self::UPDATE_EVENT_SQL);
        $this->addStatement->execute($data);
    }

    public function hasEvent(int|string $sourceId, string $channel, string $timestamp): bool
    {
        $this->hasEventStatement ??= $this->con->prepare(self::HAS_EVENT_SQL);
        $this->hasEventStatement->execute([
            ':source_id' => $sourceId,
            ':channel' => $channel,
            ':timestamp' => $timestamp,
        ]);

        return !empty($this->hasEventStatement->fetch());
    }

    public function hasEvents(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($keys), '(?, ?, ?)'));
        $params = [];
        foreach ($keys as [$sourceId, $channel, $timestamp]) {
            $params[] = $sourceId;
            $params[] = $channel;
            $params[] = $timestamp;
        }

        $statement = $this->con->prepare(
            'SELECT source_id, channel, "timestamp" FROM event WHERE (source_id, channel, "timestamp") IN (' . $placeholders . ')'
        );
        $statement->execute($params);

        // Postgres renders the timestamp in its own text form, so the comparison is on parsed values
        $existing = [];
        foreach ($statement->fetchAll(PDO::FETCH_NUM) as [$sourceId, $channel, $timestamp]) {
            $existing[$sourceId . '|' . $channel][] = new DateTimeImmutable((string)$timestamp);
        }

        $found = [];
        foreach ($keys as $index => [$sourceId, $channel, $timestamp]) {
            $wanted = new DateTimeImmutable($timestamp);
            foreach ($existing[$sourceId . '|' . $channel] ?? [] as $candidate) {
                if ($candidate->format('Y-m-d H:i:s.u') === $wanted->format('Y-m-d H:i:s.u')) {
                    $found[$index] = true;
                    break;
                }
            }
        }

        return $found;
    }

    public function addMany(array $messages, string $channel): void
    {
        if ($messages === []) {
            return;
        }

        $rowPlaceholders = '(' . implode(', ', array_fill(0, count(self::COLUMNS), '?')) . ', NOW())';
        $placeholders = implode(', ', array_fill(0, count($messages), $rowPlaceholders));
        $params = [];
        foreach ($messages as $message) {
            $data = $this->mapper->map(message: $message, channel: $channel);
            foreach (self::COLUMNS as $column) {
                $params[] = $data[':' . $column] ?? null;
            }
        }

        $this->con->beginTransaction();
        try {
            $statement = $this->con->prepare(
                'INSERT INTO event(source_id, name, channel, correlation_id, user_id, aggregate_id, aggregate_version, data, "timestamp", received_at) VALUES '
                . $placeholders
            );
            $statement->execute($params);
            $this->con->commit();
        } catch (Throwable $e) {
            if ($this->con->inTransaction()) {
                $this->con->rollBack();
            }

            throw $e;
        }
    }
}
