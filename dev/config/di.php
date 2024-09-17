<?php

declare(strict_types=1);

use Application\Event\Store;
use Application\Execution\Process;
use Application\Http\Handler as HttpHandler;
use Application\Http\Impl\PingHandler;
use Application\Http\Server;
use Application\Messaging\Consumer;
use Application\Messaging\Handler as MessageHandler;
use Application\Messaging\Impl\EventHandler;
use Infrastructure\Event\Adapter\Pdo\Mapper;
use Infrastructure\Event\Adapter\Postgres\Store as PostgresStore;
use Infrastructure\Execution\Adapter\Swoole\Process as SwooleProcess;
use Infrastructure\Http\Adapter\Swoole\Server as SwooleServer;
use Infrastructure\Messaging\Adapter\EnqueueRdkafka\Consumer as KafkaConsumer;

return [
    Server::class => DI\get(SwooleServer::class),
    SwooleServer::class => DI\autowire()->constructorParameter('port', (int)getenv('HTTP_PORT')),
    HttpHandler::class => DI\get(PingHandler::class),
    Consumer::class => DI\autowire(KafkaConsumer::class),
    MessageHandler::class => DI\autowire(EventHandler::class),
    Process::class => DI\autowire(SwooleProcess::class),
    Store::class => DI\autowire(PostgresStore::class)->constructorParameter('mapper', DI\get(Mapper::class)),
];
