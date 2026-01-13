<?php

declare(strict_types=1);

namespace Application\Messaging;

interface Filter
{
    /**
     * @param array<int, string> $arg
     */
    public function __construct(array $arg);

    public function matches(Message $message): bool;
}
