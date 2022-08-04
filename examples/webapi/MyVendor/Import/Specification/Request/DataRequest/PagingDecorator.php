<?php

declare(strict_types=1);

namespace MyVendor\Import\Specification\Request\DataRequest;

use Jh\Import\Source\Webapi\DataRequest\PagingDecoratorInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;

class PagingDecorator implements PagingDecoratorInterface
{
    private UriFactoryInterface $uriFactory;

    public function __construct(UriFactoryInterface $uriFactory)
    {
        $this->uriFactory = $uriFactory;
    }

    public function decorate(RequestInterface $request, int $dataRequestPageSize, int $page): RequestInterface
    {
        $uri = $this->uriFactory->createUri($request->getUri()->__toString());
        $uriWithQueryParam = $uri->withQuery(
            http_build_query(['limit' => $dataRequestPageSize, 'page' => $page])
        );

        return $request->withUri($uriWithQueryParam);
    }
}
