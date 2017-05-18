<?php

namespace Jh\Import\Import;

use Jh\Import\Archiver\Factory;
use Jh\Import\Locker\Locker;
use Jh\Import\Progress\CliProgress;
use Jh\Import\Report\ReportFactory;
use Jh\Import\Report\ReportPersister;
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
     * @var CliProgress
     */
    private $cliProgress;

    public function __construct(
        ReportFactory $reportFactory,
        Factory $archiverFactory,
        State $appState,
        Locker $locker,
        History $history,
        IndexerRegistry $indexerRegistry,
        CliProgress $cliProgress
    ) {
        $this->reportFactory = $reportFactory;
        $this->archiverFactory = $archiverFactory;
        $this->appState = $appState;
        $this->locker = $locker;
        $this->history = $history;
        $this->indexerRegistry = $indexerRegistry;
        $this->cliProgress = $cliProgress;
    }

    public function create(Source $source, ImportSpecification $specification, Writer $writer) : Importer
    {
        $progress = null;
        if ($this->appState->getMode() === State::MODE_DEVELOPER || posix_isatty(STDOUT)) {
            $progress = $this->cliProgress;
        }

        return new Importer(
            $source,
            $specification,
            $writer,
            $this->reportFactory,
            $this->archiverFactory,
            $this->locker,
            $this->history,
            $this->indexerRegistry,
            $progress
        );
    }
}
