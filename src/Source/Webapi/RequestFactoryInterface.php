<?php

declare(strict_types=1);

namespace Jh\Import\Source\Webapi;

use Psr\Http\Message\RequestInterface;

interface RequestFactoryInterface
{
    public function create(): RequestInterface;
}
