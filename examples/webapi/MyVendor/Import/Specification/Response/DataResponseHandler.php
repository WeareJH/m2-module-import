<?php

declare(strict_types=1);

namespace MyVendor\Import\Specification\Response;

use Jh\Import\Exception\UnexpectedResponseException;
use Jh\Import\Source\Webapi\DataResponseHandlerInterface;
use JsonException;
use Psr\Http\Message\ResponseInterface;

class DataResponseHandler implements DataResponseHandlerInterface
{
    public function handle(ResponseInterface $response): iterable
    {
        $responseBody = $response->getBody()->getContents();
        try {
            $responseBodyDecoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedResponseException(
                __($exception->getMessage()),
                $response
            );
        }

        if (!isset($responseBodyDecoded['customers'])) {
            throw new UnexpectedResponseException(
                __('No data provided'),
                $response
            );
        }

        return $responseBodyDecoded['customers'];
    }
}
