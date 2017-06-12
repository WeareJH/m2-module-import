<?php

namespace Jh\Import\Command;

use Jh\Import\Config\Data;
use Jh\Import\Entity\ImportHistoryResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
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
class ClearLastImportLogCommand extends Command
{

    /**
     * @var Data
     */
    private $importConfig;

    /**
     * @var AdapterInterface
     */
    private $dbAdapter;

    public function __construct(Data $importConfig, ResourceConnection $resourceConnection)
    {
        $this->importConfig = $importConfig;
        $this->dbAdapter = $resourceConnection->getConnection();
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('import:clear-last')
            ->setDescription('Clear the logs for the last import')
            ->addArgument(
                'import_name',
                InputArgument::REQUIRED,
                'The name of the import you wish to remove the last set of logs for as defined in imports.xml'
            );
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

        $select = $this->dbAdapter
            ->select()
            ->from('jh_import_history', ['id'])
            ->where('import_name = ?', $importName)
            ->order('id DESC');

        $results = $this->dbAdapter->fetchAll($select);

        if (count($results) === 0) {
            throw new \RuntimeException(sprintf('No imports with name: "%s" have completed yet', $importName));
        }

        $id = $results[0]['id'];

        $this->dbAdapter->delete('jh_import_history_item_log', ['history_id = ?' => $id]);
        $this->dbAdapter->delete('jh_import_history_log', ['history_id = ?' => $id]);
        $this->dbAdapter->delete('jh_import_history', ['id = ?' => $id]);

        $output->writeln(
            sprintf(
                '<info>Removed the last set of logs for import: "%s". You can now reimport the same source.</info>',
                $importName
            )
        );
    }
}
