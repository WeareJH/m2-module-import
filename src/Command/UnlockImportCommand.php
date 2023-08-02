<?php

namespace Jh\Import\Command;

use Jh\Import\Config\Data;
use Jh\Import\Locker\Locker;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class UnlockImportCommand extends Command
{
    /**
     * @var Data
     */
    private $importConfig;

    /**
     * @var Locker
     */
    private $locker;

    public function __construct(Data $importConfig, Locker $locker)
    {
        $this->importConfig = $importConfig;
        $this->locker = $locker;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('jh-import:unlock')
            ->setDescription('Unlock a locked import')
            ->addArgument('import_name', InputArgument::REQUIRED, 'The import to run as defined in imports.xml');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $importName = $input->getArgument('import_name');

        if (!$this->importConfig->hasImport($importName)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot find configuration for import with name: "%s"', $importName)
            );
        }

        if (!$this->locker->locked($importName)) {
            throw new \RuntimeException(sprintf('Import: "%s" is not locked', $importName));
        }

        $this->locker->release($importName);
        $output->writeln(sprintf('<info>The lock for import: "%s" has been released</info>', $importName));

        return Cli::RETURN_SUCCESS;
    }
}
