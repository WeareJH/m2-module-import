<?php

declare(strict_types=1);

namespace Jh\Import\Import;

use Jh\Import\Config;
use Jh\Import\Report\Report;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview\View\StateInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Indexer
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(IndexerRegistry $indexerRegistry, OutputInterface $output)
    {
        $this->indexerRegistry = $indexerRegistry;
        $this->output = $output;
    }

    public function disable(Config $config): void
    {
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

    public function index(Config $config, Result $result, Report $report): void
    {
        //if the writer return a result with a list of affected ids
        //we reindex all the ids using the indexers specified in the config
        if ($result->hasAffectedIds()) {
            $report->addInfo("Indexing ({$result->affectedIdsCount()}) affected item(s)");
            $this->output->writeln([
               "<bg=magenta>Indexing ({$result->affectedIdsCount()}) affected item(s)</>",
               ''
            ]);
            $chunkedIds = array_chunk($result->getAffectedIds(), 1000);

            foreach ($config->getIndexers() as $indexerId) {
                $report->addInfo("Running Indexer: {$indexerId}");
                $this->output->writeln("  <fg=magenta>Running Indexer: {$indexerId}</>");
                try {
                    $indexer = $this->indexerRegistry->get($indexerId);
                } catch (\InvalidArgumentException $e) {
                    continue;
                }

                foreach ($chunkedIds as $ids) {
                    $indexer->reindexList($ids);
                }
                $report->addInfo("Finished Indexer: {$indexerId}");
            }
            $report->addInfo("Finished Indexing");
            $this->output->writeln(['', '<bg=magenta>Finished Indexing</>']);
        }
    }
}
