<?php

declare(strict_types=1);

namespace Application\Messaging\Impl;

use Application\Event\Mapper;
use Application\Event\Store;
use Application\Messaging\Filter;
use Application\Messaging\Handler;
use Application\Messaging\Message;
use Application\Messaging\Translator;
use PHPUnit\Framework\TestCase;

final class EventHandlerTest extends TestCase
{
    public function testBatchStoresNewMessagesSkipsDuplicatesAndReportsInvalidOnes(): void
    {
        $store = new InMemoryStore();
        $store->existing[] = [7, 'ch', '2024-01-01 10:00:00'];   // already in the database
        $handler = new EventHandler($store);

        $outcomes = $handler->handleBatch([
            $this->message(id: 1, timestamp: '2024-01-01 10:00:01'),
            $this->message(id: 7, timestamp: '2024-01-01 10:00:00'),   // duplicate of the database row
            $this->message(id: 1, timestamp: '2024-01-01 10:00:01'),   // duplicate inside the batch
            $this->message(id: null, timestamp: '2024-01-01 10:00:02'), // invalid: no id
            $this->message(id: 2, timestamp: '2024-01-01 10:00:03'),
        ], 'ch');

        self::assertSame(Handler::STORED, $outcomes[0]['status']);
        self::assertSame(Handler::DUPLICATE, $outcomes[1]['status']);
        self::assertSame(Handler::DUPLICATE, $outcomes[2]['status']);
        self::assertSame(Handler::INVALID, $outcomes[3]['status']);
        self::assertStringContainsString('empty id', $outcomes[3]['exception']->getMessage());
        self::assertSame(Handler::STORED, $outcomes[4]['status']);

        self::assertSame(1, $store->addManyCalls, 'one transaction for the whole batch');
        self::assertSame([1, 2], array_map(static fn (Message $m) => $m->getProperty('id'), $store->stored));
    }

    public function testFilterAndTranslatorApplyPerMessage(): void
    {
        $store = new InMemoryStore();
        $filter = new readonly class() implements Filter {
            public function __construct(array $arg = [])
            {
            }

            public function matches(Message $message): bool
            {
                return $message->getProperty('id') !== 2;
            }
        };
        $translator = new readonly class() implements Translator {
            public function __construct(array $arg = [])
            {
            }

            public function translate(Message $message): Message
            {
                return $message->withHeader('name', 'translated');
            }
        };
        $handler = new EventHandler($store, $filter, $translator);

        $outcomes = $handler->handleBatch([
            $this->message(id: 1, timestamp: '2024-01-01 10:00:01'),
            $this->message(id: 2, timestamp: '2024-01-01 10:00:02'),
        ], 'ch');

        self::assertSame(Handler::STORED, $outcomes[0]['status']);
        self::assertSame(Handler::FILTERED, $outcomes[1]['status']);
        self::assertSame('translated', $store->stored[0]->getHeader('name'));
    }

    public function testSingleHandleThrowsForAnInvalidMessage(): void
    {
        $handler = new EventHandler(new InMemoryStore());

        $this->expectExceptionMessage('empty timestamp');
        $handler->handle($this->message(id: 1, timestamp: null), 'ch');
    }

    private function message(mixed $id, ?string $timestamp): Message
    {
        $properties = array_filter(['id' => $id, 'timestamp' => $timestamp], static fn ($v) => $v !== null);

        return new ArrayMessage('{}', ['name' => 'eventName'], $properties);
    }
}

/** A store that records what it was asked, instead of Postgres */
final class InMemoryStore implements Store
{
    /** @var array<int, array{0: int|string, 1: string, 2: string}> */
    public array $existing = [];

    /** @var Message[] */
    public array $stored = [];

    public int $addManyCalls = 0;

    public function __construct(?Mapper $mapper = null)
    {
    }

    public function add(Message $message, string $channel): void
    {
        $this->stored[] = $message;
    }

    public function hasEvent(int|string $sourceId, string $channel, string $timestamp): bool
    {
        return $this->hasEvents([[$sourceId, $channel, $timestamp]]) !== [];
    }

    public function hasEvents(array $keys): array
    {
        $found = [];
        foreach ($keys as $index => $key) {
            if (in_array($key, $this->existing, true)) {
                $found[$index] = true;
            }
        }

        return $found;
    }

    public function addMany(array $messages, string $channel): void
    {
        $this->addManyCalls++;
        foreach ($messages as $message) {
            $this->stored[] = $message;
        }
    }
}

/** A plain message for tests, no broker behind it */
final class ArrayMessage implements Message
{
    public function __construct(private readonly string $body, private array $headers = [], private array $properties = [])
    {
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, mixed $default = null): mixed
    {
        return $this->headers[$name] ?? $default;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(string $name, mixed $default = null): mixed
    {
        return $this->properties[$name] ?? $default;
    }

    public function getKey(): ?string
    {
        return null;
    }

    public function withBody(string $body): Message
    {
        return new self($body, $this->headers, $this->properties);
    }

    public function withHeader(string $name, mixed $value): Message
    {
        return new self($this->body, [$name => $value] + $this->headers, $this->properties);
    }

    public function withProperty(string $name, mixed $value): Message
    {
        return new self($this->body, $this->headers, [$name => $value] + $this->properties);
    }

    public function withoutHeader(string $name): Message
    {
        $headers = $this->headers;
        unset($headers[$name]);

        return new self($this->body, $headers, $this->properties);
    }

    public function withoutProperty(string $name): Message
    {
        $properties = $this->properties;
        unset($properties[$name]);

        return new self($this->body, $this->headers, $properties);
    }
}
