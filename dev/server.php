<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Application\Event\Store;
use Application\Execution\Process;
use Application\Http\Handler as HttpHandler;
use Application\Http\Request as HttpRequest;
use Application\Http\Response as HttpResponse;
use Application\Http\Server as HttpServer;
use Application\Messaging\Consumer as MessagingConsumer;
use Application\Messaging\Handler as MessagingHandler;
use DI\Container;
use DI\ContainerBuilder;

$builder = new ContainerBuilder();
$builder->addDefinitions('config/di.php');
$container = $builder->build();

$httpServer = $container->get(HttpServer::class);
$httpHandler = $container->get(HttpHandler::class);

$messagingConfig = include 'config/messaging.php';

foreach ($messagingConfig['channels'] as $channel => $handlerConfig) {
    $process = $container->make(
        Process::class,
        [
            'callback' => function (/* $process */) use ($messagingConfig, $container, $channel, $handlerConfig) {
                echo "Starting process...\n";

                $messagingConsumer = buildListener(
                    handlerConfig: $handlerConfig,
                    messagingConfig: $messagingConfig,
                    channel: $channel,
                    container: $container
                );

                $messagingConsumer->start();
                sleep(1);
            }
        ]
    );

    $httpServer->addProcess($process);
}

$httpServer->on(
    'start',
    function (/* HttpServer $httpServer */) {
        echo "HTTP httpServer is started.\n";
    }
);

$httpServer->on(
    'request',
    function (HttpRequest $request, HttpResponse $response) use ($httpHandler) {
        $httpHandler->handle($request, $response);
    }
);

$httpServer->start();

function buildListener(array $handlerConfig, array $messagingConfig, string $channel, Container $container): MessagingConsumer
{
    $filter = $handlerConfig['filter'] ?
        $container->make(
            $handlerConfig['filter']['class'],
            ['arg' => $handlerConfig['filter']['arg']]
        ) :
        null;

    $translator = $handlerConfig['translator'] ?
        $container->make(
            $handlerConfig['translator']['class'],
            ['arg' => $handlerConfig['translator']['arg']]
        ) :
        null;

    return $container->make(MessagingConsumer::class, [
        'config' => $messagingConfig['connection'],
        'channel' => $channel,
        'invalidChannel' => $messagingConfig['invalidChannel'],
        'handler' => $container->make(MessagingHandler::class, [
            'store' => $container->get(Store::class),
            'filter' => $filter,
            'translator' => $translator
        ])
    ]);
}
