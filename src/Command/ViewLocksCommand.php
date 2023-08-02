<?php

namespace Jh\Import\Command;

use Jh\Import\Config\Data;
use Jh\Import\Locker\Locker;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ViewLocksCommand extends Command
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
        $this->setName('jh-import:locks')
            ->setDescription('Show current locks');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locks = array_map(
            function (string $importName) {
                return [$importName];
            },
            array_filter(
                $this->importConfig->getAllImportNames(),
                function (string $importName) {
                    return $this->locker->locked($importName);
                }
            )
        );

        if (empty($locks)) {
            $output->writeln(['', '<comment>No import is locked</comment>', '']);
            return;
        }

        $output->writeln('');
        $output->writeln('<comment>All locked imports:</comment>');
        $output->writeln('');

        (new Table($output))
            ->setHeaders(['Locks'])
            ->setRows($locks)
            ->render();

        $output->writeln('');

        return Cli::RETURN_SUCCESS;
    }
}
