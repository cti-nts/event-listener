<?php

declare(strict_types=1);

namespace Application\Execution;

use Swoole\Process as SwooleProcess;

interface Process
{
    public function __construct(callable $callback);

    public function getDelegate(): SwooleProcess;
}
