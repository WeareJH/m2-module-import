<?php

declare(strict_types=1);

namespace MyVendor\Import\Specification\Request;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use Jh\Import\Source\Webapi\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class CountRequestFactory implements RequestFactoryInterface
{
    private HttpFactory $httpFactory;

    public function __construct(HttpFactory $httpFactory)
    {
        $this->httpFactory = $httpFactory;
    }

    public function create(): RequestInterface
    {
        $request = $this->httpFactory->createRequest(
            'GET',
            '<url>'
        );

        $user = '<user>';
        $password = '<password>';
        $value = sprintf('Basic %s', base64_encode(sprintf('%s:%s', $user, $password)));

        return $request->withHeader('Authorization', $value);
    }
}
