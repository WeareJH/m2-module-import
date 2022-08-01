<?php

declare(strict_types=1);

namespace MyVendor\Import\Specification\Response;

use Jh\Import\Exception\UnexpectedResponseException;
use Jh\Import\Source\Webapi\CountResponseHandlerInterface;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use function json_decode;

class CountResponseHandler implements CountResponseHandlerInterface
{
    public function handle(ResponseInterface $response): int
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

        if (!isset($responseBodyDecoded['count'])) {
            throw new UnexpectedResponseException(
                __('No entity count provided'),
                $response
            );
        }

        return (int) $responseBodyDecoded['count'];
    }
}
