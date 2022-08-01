<?php

declare(strict_types=1);

namespace Jh\Import\Source\Webapi\DataRequest;

use Psr\Http\Message\RequestInterface;

interface FilterDecoratorInterface
{
    public function decorate(RequestInterface $request): RequestInterface;
}
