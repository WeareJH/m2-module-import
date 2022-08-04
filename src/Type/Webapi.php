<?php

declare(strict_types=1);

namespace Jh\Import\Type;

use Jh\Import\Config;
use Jh\Import\Import\ImporterFactory;
use Magento\Framework\ObjectManagerInterface;

class Webapi implements Type
{
    private ObjectManagerInterface $objectManager;
    private ImporterFactory $importerFactory;

    public function __construct(
        ObjectManagerInterface $objectManager,
        ImporterFactory $importerFactory
    ) {
        $this->objectManager = $objectManager;
        $this->importerFactory = $importerFactory;
    }

    public function run(Config $config): void
    {
        $specification = $this->objectManager->get($config->getSpecificationService());
        $writer = $this->objectManager->get($config->getWriterService());
        $countRequestFactory = $this->objectManager->create($config->getCountRequestFactory());
        $countResponseHandler = $this->objectManager->create($config->getCountResponseHandler());
        $dataRequestFactory = $this->objectManager->create($config->getDataRequestFactory());
        $dataRequestPagingDecorator = $this->objectManager->create($config->getDataRequestPagingDecorator());
        $config->getDataRequestFilterDecorator()
            ? $dataRequestFilterDecorator = $this->objectManager->create($config->getDataRequestFilterDecorator())
            : $dataRequestFilterDecorator = null;

        $dataResponseHandler = $this->objectManager->create($config->getDataResponseHandler());
        $source = $this->objectManager->create(
            $config->getSourceService(),
            [
                'idField' => $config->getIdField(),
                'sourceId' => (string) $config->getSourceId(),
                'countRequestFactory' => $countRequestFactory,
                'countResponseHandler' => $countResponseHandler,
                'dataRequestFactory' => $dataRequestFactory,
                'dataRequestPageSize' => $config->getDataRequestPageSize(),
                'dataRequestPagingDecorator' => $dataRequestPagingDecorator,
                'dataResponseHandler' => $dataResponseHandler,
                'dataRequestFilterDecorator' => $dataRequestFilterDecorator
            ]
        );

        $this->importerFactory
            ->create($source, $specification, $writer)
            ->process($config);
    }
}
