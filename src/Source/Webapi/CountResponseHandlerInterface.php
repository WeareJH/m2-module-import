<?php

declare(strict_types=1);

namespace Jh\Import\Source\Webapi;

use Psr\Http\Message\ResponseInterface;

interface CountResponseHandlerInterface
{
    public function handle(ResponseInterface $response): int;
}
