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
    $connectionConfig['global']['security.protocol'] = 'SASL_SSL';
    $connectionConfig['global']['sasl.mechanisms'] = getenv('MESSAGE_BROKER_SASL_MECHANISMS') ?: 'PLAIN';
    $connectionConfig['global']['sasl.username'] = getenv('MESSAGE_BROKER_SASL_USERNAME') ?: '$ConnectionString';
    $connectionConfig['global']['sasl.password'] = getenv('MESSAGE_BROKER_SASL_PASSWORD');
}

$channels = array_filter(array_map(trim(...), explode("\n", getenv('EVENT_CHANNELS'))), static fn ($value) => $value !== '' && $value !== '0');

$channelsConfig = array_reduce($channels, function (array $carry, string $item) {
    $parts = array_map(trim(...), explode(";", $item));

    $classConfig = function (string $configStr) {
        $configStrParts = explode("|", $configStr);
        $className = trim(array_shift($configStrParts));
        $argumentArray = array_map(trim(...), $configStrParts);
        return $className ? [
            'class' => $className,
            'arg' => $argumentArray
        ] : null;
    };

    $carry[$parts[0]] = [
        'filter' => count($parts) > 1 ? $classConfig($parts[1]) : null,
        'translator' => count($parts) > 2 ? $classConfig($parts[2]) : null,
    ];

    return $carry;
}, []);

return [
    'connection' => $connectionConfig,
    'channels' => $channelsConfig,
    'invalidChannel' => getenv('INVALID_CHANNEL') ?: null
];
