<?php

namespace Jh\Import\Command;

use Jh\Import\Config\Data;
use Magento\Framework\Console\Cli;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ViewLogsCommand extends Command
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
        $this->setName('jh-import:view-logs')
            ->setDescription('Show the logs for a recent import')
            ->addArgument(
                'import_name',
                InputArgument::REQUIRED,
                'The import name to view logs for as defined in imports.xml'
            )
            ->addArgument('num_logs', InputArgument::OPTIONAL, 'The number of import logs to show');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $input->setInteractive(true);
        $importName = $input->getArgument('import_name');

        if (!$this->importConfig->hasImport($importName)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot find configuration for import with name: "%s"', $importName)
            );
        }

        $output->writeln('');

        $collection = $this->collectionFactory->getReport('import_history_listing_data_source');
        $collection->addFieldToFilter('import_name', $importName);

        if ($collection->count() === 0) {
            throw new \RuntimeException(sprintf('No import with name: "%s" have completed yet', $importName));
        }

        $output->writeln(sprintf('<comment>Imports with name: "%s" listed below:</comment>', $importName));
        $output->writeln('');

        (new Table($output))
            ->setHeaders(['ID', 'Started', 'Finished', 'Memory Usage'])
            ->setRows(array_map(function ($import) {
                $started  = \DateTime::createFromFormat('Y-m-d H:i:s', $import->getData('started'));
                $finished = $import->getData('finished')
                    ? \DateTime::createFromFormat('Y-m-d H:i:s', $import->getData('finished'))->format('d-m-Y H:i:s')
                    : 'N/A';

                $memory = $import->getData('memory_usage')
                    ? format_bytes($import->getData('memory_usage'))
                    : 'N/A';

                return [$import->getId(), $started->format('d-m-Y H:i:s'), $finished, $memory];
            }, $collection->getItems()))
            ->render();

        $output->writeln('');
        $helper = $this->getHelper('question');
        $question = new Question('<question>Please enter the ID of the import you want to view:</question> ');
        $question->setValidator(function ($answer) use ($collection) {
            if (!is_int($answer) && !in_array($answer, $collection->getAllIds())) {
                throw new \RuntimeException('Invalid ID entered');
            }
            return $answer;
        });

        $importId = $helper->ask($input, $output, $question);

        $itemLogCollection = $this->collectionFactory->getReport('import_history_item_log_listing_data_source');
        $itemLogCollection->addFieldToFilter('history_id', $importId);

        if ($itemLogCollection->count() === 0) {
            throw new \RuntimeException(sprintf('There are no log entries for import with ID: "%d"', $importId));
        }

        if ($input->getArgument('num_logs')) {
            $itemLogCollection->getSelect()->limit($input->getArgument('num_logs'));
        }

        $output->writeln('');
        (new Table($output))
            ->setHeaders(['Created', 'Log Level', 'Reference Line', 'ID Field', 'ID Value', 'Message'])
            ->setRows(array_map(function ($log) {
                return [
                    \DateTime::createFromFormat('Y-m-d H:i:s', $log->getData('created'))->format('d-m-Y H:i:s'),
                    $log->getData('log_level'),
                    $log->getData('reference_line'),
                    $log->getData('id_field'),
                    $log->getData('id_value'),
                    $log->getData('message'),
                ];
            }, $itemLogCollection->getItems()))
            ->render();
        
        return Cli::RETURN_SUCCESS;
    }
}
