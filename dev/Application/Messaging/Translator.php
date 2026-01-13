<?php

declare(strict_types=1);

namespace Application\Messaging;

interface Translator
{
    /**
     * @param array<int, string> $arg
     */
    public function __construct(array $arg);

    public function translate(Message $message): Message;
}
