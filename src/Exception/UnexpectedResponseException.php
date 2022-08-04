<?php

declare(strict_types=1);

namespace Jh\Import\Exception;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class UnexpectedResponseException extends LocalizedException implements ClientExceptionInterface
{
    private ResponseInterface $response;

    public function __construct(
        Phrase $phrase,
        ResponseInterface $response,
        Exception $cause = null,
        $code = 0
    ) {
        parent::__construct($phrase, $cause, $code);
        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
