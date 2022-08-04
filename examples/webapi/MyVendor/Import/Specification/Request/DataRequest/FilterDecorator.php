<?php

declare(strict_types=1);

namespace MyVendor\Import\Specification\Request\DataRequest;

use Jh\Import\Source\Webapi\DataRequest\FilterDecoratorInterface;
use Psr\Http\Message\RequestInterface;

class FilterDecorator implements FilterDecoratorInterface
{
    public function decorate(RequestInterface $request): RequestInterface
    {
        return $request;
    }
}
