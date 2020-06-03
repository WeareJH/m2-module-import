<?php

namespace Jh\Import\Command;

use Jh\Import\Config\Data;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ListImportsCommand extends Command
{

    /**
     * @var Data
     */
    private $importConfig;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(Data $importConfig, CollectionFactory $collectionFactory)
    {
        $this->importConfig = $importConfig;
        $this->collectionFactory = $collectionFactory;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('jh-import:list-imports')
            ->setDescription('List all of the registered imports');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $input->setInteractive(true);

        $output->writeln('');

        $output->writeln('<comment>All imports registered with the system:</comment>');
        $output->writeln('');

        (new Table($output))
            ->setHeaders(['Name', 'Type', 'Match Files', 'Incoming Directory', 'Cron Expr'])
            ->setRows(array_map(function ($import) {
                $config = $this->importConfig->getImportConfigByName($import);

                return [
                    $config->getImportName(),
                    $config->getType(),
                    $config->get('match_files'),
                    $config->get('incoming_directory'),
                    $config->hasCron() ? $config->getCron() : 'N/A',
                ];
            }, $this->importConfig->getAllImportNames()))
            ->render();

        $output->writeln('');
    }
}
