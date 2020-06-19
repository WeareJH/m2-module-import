<?php

namespace Jh\Import\Import;

use Jh\Import\Archiver\Factory;
use Jh\Import\Locker\Locker;
use Jh\Import\Output\Factory as OutputFactory;
use Jh\Import\Progress\Factory as ProgressFactory;
use Jh\Import\Progress\CliProgress;
use Jh\Import\Report\ReportFactory;
use Jh\Import\Source\Source;
use Jh\Import\Specification\ImportSpecification;
use Jh\Import\Writer\Writer;
use Magento\Framework\App\State;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ImporterFactory
{
    /**
     * @var ReportFactory
     */
    private $reportFactory;

    /**
     * @var Factory
     */
    private $archiverFactory;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var Locker
     */
    private $locker;

    /**
     * @var History
     */
    private $history;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var ProgressFactory
     */
    private $progressFactory;

    /**
     * @var OutputFactory
     */
    private $outputFactory;

    public function __construct(
        ReportFactory $reportFactory,
        Factory $archiverFactory,
        State $appState,
        Locker $locker,
        History $history,
        IndexerRegistry $indexerRegistry,
        ProgressFactory $progressFactory,
        OutputFactory $outputFactory
    ) {
        $this->reportFactory = $reportFactory;
        $this->archiverFactory = $archiverFactory;
        $this->appState = $appState;
        $this->locker = $locker;
        $this->history = $history;
        $this->indexerRegistry = $indexerRegistry;
        $this->progressFactory = $progressFactory;
        $this->outputFactory = $outputFactory;
    }

    public function create(Source $source, ImportSpecification $specification, Writer $writer): Importer
    {
        return new Importer(
            $source,
            $specification,
            $writer,
            $this->reportFactory,
            $this->archiverFactory,
            $this->locker,
            $this->history,
            new Indexer($this->indexerRegistry, $this->outputFactory->get()),
            $this->progressFactory->get()
        );
    }
}
