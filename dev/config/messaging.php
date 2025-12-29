<?php

declare(strict_types=1);

$connectionConfig = [
    'global' => [
        'metadata.broker.list' => getenv('MESSAGE_BROKER_HOST') . ':' . getenv('MESSAGE_BROKER_PORT'),
        'group.id' => getenv('MESSAGE_CONSUMER_GROUP'),
    ],
    'topic' => [
        'auto.offset.reset' => 'earliest',
        'enable.auto.commit' => 'false'
    ],
];

if (getenv('MESSAGE_BROKER_SECURITY_PROTOCOL') === 'SASL_SSL') {
    $connectionConfig['global'] += [
        'security.protocol' => 'SASL_SSL',
        'sasl.mechanisms' => getenv('MESSAGE_BROKER_SASL_MECHANISMS') ?: 'PLAIN',
        'sasl.username' => getenv('MESSAGE_BROKER_SASL_USERNAME') ?: '$ConnectionString',
        'sasl.password' => getenv('MESSAGE_BROKER_SASL_PASSWORD'),
    ];
}

$channels = array_filter(
    array_map(trim(...), explode("\n", getenv('EVENT_CHANNELS'))),
    static fn ($value) => $value !== '' && $value !== '0'
);

$classConfig = function (?string $configStr): ?array {
    if ($configStr === null || $configStr === '') {
        return null;
    }

    $configParts = explode("|", $configStr);
    $className = trim(array_shift($configParts));
    $arguments = array_map(trim(...), $configParts);

    return $className ? [
        'class' => $className,
        'arg' => $arguments
    ] : null;
};

$channelsConfig = array_reduce($channels, function (array $carry, string $channelConfig) use ($classConfig): array {
    $parts = array_map(trim(...), explode(";", $channelConfig));

    [$channelName, $filterConfig, $translatorConfig] = $parts + [null, null, null];

    $carry[$channelName] = [
        'filter' => $classConfig($filterConfig),
        'translator' => $classConfig($translatorConfig),
    ];

    return $carry;
}, []);

return [
    'connection' => $connectionConfig,
    'channels' => $channelsConfig,
    'invalidChannel' => getenv('INVALID_CHANNEL') ?: null
];
