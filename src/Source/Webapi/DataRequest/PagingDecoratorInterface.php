<?php

declare(strict_types=1);

namespace Jh\Import\Source\Webapi\DataRequest;

use Psr\Http\Message\RequestInterface;

interface PagingDecoratorInterface
{
    public function decorate(RequestInterface $request, int $dataRequestPageSize, int $page): RequestInterface;
}
