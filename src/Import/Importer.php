<?php

namespace Jh\Import\Import;

use Jh\Import\Archiver\Factory as ArchiverFactory;
use Jh\Import\Config;
use Jh\Import\Locker\ImportLockedException;
use Jh\Import\Locker\Locker;
use Jh\Import\Progress\NullProgress;
use Jh\Import\Progress\Progress;
use Jh\Import\Report\CollectingReport;
use Jh\Import\Report\ConsoleLoggingReportDecorator;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportFactory;
use Jh\Import\Report\ReportItem;
use Jh\Import\Source\Source;
use Jh\Import\Specification\ImportSpecification;
use Jh\Import\Writer\Writer;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview\View\StateInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Importer
{
    /**
     * @var Source
     */
    private $source;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var ReportFactory
     */
    private $reportFactory;

    /**
     * @var callable[]
     */
    private $filters = [];

    /**
     * @var callable[]
     */
    private $transformers = [];

    /**
     * @var Progress
     */
    private $progress;

    /**
     * @var Locker
     */
    private $locker;

    /**
     * @var History
     */
    private $history;

    /**
     * @var ArchiverFactory
     */
    private $archiverFactory;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    public function __construct(
        Source $source,
        ImportSpecification $importSpecification,
        Writer $writer,
        ReportFactory $reportFactory,
        ArchiverFactory $archiverFactory,
        Locker $locker,
        History $history,
        IndexerRegistry $indexerRegistry,
        Progress $progress = null
    ) {
        $this->source = $source;
        $this->writer = $writer;
        $this->progress = $progress ?: new NullProgress;
        $this->archiverFactory = $archiverFactory;
        $this->reportFactory = $reportFactory;
        $this->locker = $locker;
        $this->history = $history;
        $this->indexerRegistry = $indexerRegistry;

        $importSpecification->configure($this);
    }

    /**
     * @param callable $filter
     * @return void
     */
    public function filter(callable $filter)
    {
        $this->filters[] = $filter;
    }

    public function transform(callable $transform)
    {
        $this->transformers[] = $transform;
    }

    private function canImport(string $importName, Report $report) : bool
    {
        if ($this->history->isImported($this->source)) {
            $report->addError('This import source has already been imported.');
            return false;
        }

        try {
            //check if an import by this name is already running
            $this->locker->lock($importName);
        } catch (ImportLockedException $e) {
            $report->addError($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @param Config $config
     * @return void
     */
    public function process(Config $config)
    {
        $report = $this->reportFactory->createFromSourceAndConfig($this->source, $config);
        $report->start();

        $this->prepare($config);

        if (!$this->canImport($config->getImportName(), $report)) {
            $this->endReport($report);
            return;
        }

        try {
            $this->traverseSource($report, $config);
        } catch (\Exception $e) {
            $report->addError(sprintf('Could not read data from source. Error: "%s"', $e->getMessage()));
        }

        $this->finish($config);

        $archiver = $this->archiverFactory->getArchiverForSource($this->source, $config);
        if ($report->isSuccessful()) {
            $archiver->successful();
        } else {
            $archiver->failed();
        }

        $this->locker->release($config->getImportName());

        $this->endReport($report);
    }

    private function endReport(Report $report)
    {
        $report->finish(new \DateTime, memory_get_usage(true));
    }

    private function processFilters(Record $record, ReportItem $reportItem) : bool
    {
        foreach ($this->filters as $filter) {
            if (false === $filter($record, $reportItem)) {
                return false;
            }
        }

        return true;
    }

    private function processTransformers(Record $record, ReportItem $reportItem)
    {
        foreach ($this->transformers as $transformer) {
            $transformer($record, $reportItem);
        }
    }

    private function prepare(Config $config)
    {
        $this->progress->start($this->source, $config);
        $this->writer->prepare($this->source);

        //disable any indexers that may be triggered by this import
        foreach ($config->getIndexers() as $indexerId) {
            try {
                $this->indexerRegistry
                    ->get($indexerId)
                    ->getView()
                    ->getState()
                    ->setMode(StateInterface::MODE_ENABLED);
            } catch (\InvalidArgumentException $e) {
                //if flat catalog not enabled - it will throw an exception while trying to retrieve it
                continue;
            }
        }
    }

    private function finish(Config $config)
    {
        $result = $this->writer->finish($this->source);

        //if the writer return a result with a list of affected ids
        //we reindex all the ids using the indexers specified in the config
        if ($result->hasAffectedIds()) {
            $chunkedIds = array_chunk($result->getAffectedIds(), 1000);

            foreach ($config->getIndexers() as $indexerId) {
                try {
                    $indexer = $this->indexerRegistry->get($indexerId);
                } catch (\InvalidArgumentException $e) {
                    continue;
                }

                foreach ($chunkedIds as $ids) {
                    $indexer->reindexList($ids);
                }
            }
        }

        $this->progress->finish($this->source);
    }

    private function traverseSource(Report $report, Config $config)
    {
        $success = function ($rowNumber, array $row) use ($report, $config) {
            $reportItem = $report->newItem($rowNumber, $config->getIdField(), $row[$config->getIdField()] ?? '');

            $record = new Record($rowNumber, $row);


            try {
                if (!$this->processFilters($record, $reportItem)) {
                    $this->progress->advance();
                    return;
                }

                $this->processTransformers($record, $reportItem);
                $this->writer->write($record, $reportItem);
            } catch (\Exception $e) {
                $reportItem->addError($e->getMessage());
            }

            $this->progress->advance();
        };

        $error = function ($rowNumber) {
            $this->progress->advance();
        };

        $this->source->traverse($success, $error, $report);
    }

    public function getProgress() : Progress
    {
        return $this->progress;
    }
}
