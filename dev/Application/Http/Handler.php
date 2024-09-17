<?php

declare(strict_types=1);

namespace Application\Http;

interface Handler
{
    public function handle(Request $request, Response $response): void;
}
