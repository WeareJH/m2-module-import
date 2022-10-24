<?php

declare(strict_types=1);

namespace Jh\Import\Source;

use Countable;
use Exception;
use Jh\Import\Flag\PagingManager;
use Jh\Import\Report\Report;
use Jh\Import\Source\Webapi\CountResponseHandlerInterface;
use Jh\Import\Source\Webapi\DataRequest\FilterDecoratorInterface;
use Jh\Import\Source\Webapi\DataRequest\PagingDecoratorInterface;
use Jh\Import\Source\Webapi\DataResponseHandlerInterface;
use Jh\Import\Source\Webapi\RequestFactoryInterface;
use Psr\Http\Client\ClientInterface;

class Webapi implements Source, Countable
{
    private ?int $totalNumberOfItems = null;
    private string $idField;
    private string $sourceId;
    private PagingManager $pagingManager;
    private RequestFactoryInterface $countRequestFactory;
    private CountResponseHandlerInterface $countResponseHandler;
    private RequestFactoryInterface $dataRequestFactory;
    private int $dataRequestPageSize;
    private PagingDecoratorInterface $dataRequestPagingDecorator;
    private DataResponseHandlerInterface $dataResponseHandler;
    private ClientInterface $httpClient;
    private ?FilterDecoratorInterface $dataRequestFilterDecorator;

    public function __construct(
        string $idField,
        string $sourceId,
        PagingManager $pagingManager,
        RequestFactoryInterface $countRequestFactory,
        CountResponseHandlerInterface $countResponseHandler,
        RequestFactoryInterface $dataRequestFactory,
        int $dataRequestPageSize,
        PagingDecoratorInterface $dataRequestPagingDecorator,
        DataResponseHandlerInterface $dataResponseHandler,
        ClientInterface $httpClient,
        ?FilterDecoratorInterface $dataRequestFilterDecorator = null
    ) {
        $this->idField = $idField;
        $this->sourceId = $sourceId;
        $this->pagingManager = $pagingManager;
        $this->countRequestFactory = $countRequestFactory;
        $this->countResponseHandler = $countResponseHandler;
        $this->dataRequestFactory = $dataRequestFactory;
        $this->dataRequestPagingDecorator = $dataRequestPagingDecorator;
        $this->dataRequestPageSize = $dataRequestPageSize;
        $this->dataResponseHandler = $dataResponseHandler;
        $this->httpClient = $httpClient;
        $this->dataRequestFilterDecorator = $dataRequestFilterDecorator;
    }

    public function count(): int
    {
        if (!$this->totalNumberOfItems) {
            $response = $this->httpClient->sendRequest($this->countRequestFactory->create());
            $this->totalNumberOfItems = $this->countResponseHandler->handle($response);
        }

        return $this->totalNumberOfItems;
    }

    public function traverse(callable $onSuccess, callable $onError, Report $report): void
    {
        try {
            $totalNumberOfItems = $this->count();
            $pagesAmount = ceil($totalNumberOfItems / $this->dataRequestPageSize);

            for (
                $currentPage = $this->pagingManager->getValue() === null ? 1 : (int) $this->pagingManager->getValue();
                $currentPage <= $pagesAmount;
                $currentPage++
            ) {
                foreach ($this->queryData($currentPage) as $row) {
                    $onSuccess($row[$this->idField], $row);
                }

                $this->pagingManager->setValue($currentPage + 1);
            }

            $this->pagingManager->reset();
        } catch (Exception $exception) {
            $report->addError($exception->getMessage());
            $onError(null);
        }
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    private function queryData(int $page): iterable
    {
        $request = $this->dataRequestFactory->create();
        $requestDecorated = $this->dataRequestPagingDecorator->decorate($request, $this->dataRequestPageSize, $page);

        if ($this->dataRequestFilterDecorator) {
            $requestDecorated = $this->dataRequestFilterDecorator->decorate($requestDecorated);
        }

        $response = $this->httpClient->sendRequest($requestDecorated);
        $data = $this->dataResponseHandler->handle($response);

        foreach ($data as $row) {
            yield $row;
        }
    }
}
