<?php

namespace Jh\Import\Import;

use Jh\Import\Archiver\Factory as ArchiverFactory;
use Jh\Import\Config;
use Jh\Import\Locker\ImportLockedException;
use Jh\Import\Locker\Locker;
use Jh\Import\Progress\Progress;
use Jh\Import\Report\CollectingReport;
use Jh\Import\Report\ConsoleLoggingReportDecorator;
use Jh\Import\Report\Report;
use Jh\Import\Report\ReportFactory;
use Jh\Import\Report\ReportItem;
use Jh\Import\Source\Source;
use Jh\Import\Specification\ImportSpecification;
use Jh\Import\Writer\Writer;

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
     * @var Indexer
     */
    private $indexer;

    public function __construct(
        Source $source,
        ImportSpecification $importSpecification,
        Writer $writer,
        ReportFactory $reportFactory,
        ArchiverFactory $archiverFactory,
        Locker $locker,
        History $history,
        Indexer $indexer,
        Progress $progress
    ) {
        $this->source = $source;
        $this->writer = $writer;
        $this->progress = $progress;
        $this->archiverFactory = $archiverFactory;
        $this->reportFactory = $reportFactory;
        $this->locker = $locker;
        $this->history = $history;
        $this->indexer = $indexer;

        $importSpecification->configure($this);
    }

    public function filter(callable $filter): void
    {
        $this->filters[] = $filter;
    }

    public function transform(callable $transform): void
    {
        $this->transformers[] = $transform;
    }

    private function canImport(string $importName, Report $report): bool
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

    public function process(Config $config): void
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

    private function endReport(Report $report): void
    {
        $report->finish(new \DateTime(), memory_get_usage(true));
    }

    private function processFilters(Record $record, ReportItem $reportItem): bool
    {
        foreach ($this->filters as $filter) {
            if (false === $filter($record, $reportItem)) {
                return false;
            }
        }

        return true;
    }

    private function processTransformers(Record $record, ReportItem $reportItem): void
    {
        foreach ($this->transformers as $transformer) {
            $transformer($record, $reportItem);
        }
    }

    private function prepare(Config $config): void
    {
        $this->progress->start($this->source, $config);

        $this->prepareComponents($config, $this->filters);
        $this->prepareComponents($config, $this->transformers);

        $this->writer->prepare($this->source);

        $this->indexer->disable($config);
    }

    private function prepareComponents(Config $config, array $components): void
    {
        collect($components)
            ->filter(function (callable $component) {
                return $component instanceof RequiresPreparation;
            })
            ->each(function (callable $component) use ($config) {
                $component->prepare($config);
            });
    }

    private function finish(Config $config): void
    {
        $result = $this->writer->finish($this->source);

        $this->indexer->index($config, $result);

        $this->progress->finish($this->source);
    }

    private function traverseSource(Report $report, Config $config): void
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

    public function getProgress(): Progress
    {
        return $this->progress;
    }
}
