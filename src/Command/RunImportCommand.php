<?php

namespace Jh\Import\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Jh\Import\Import\Manager;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class RunImportCommand extends Command
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var State
     */
    private $state;

    public function __construct(Manager $manager, State $state)
    {
        $this->manager = $manager;
        $this->state = $state;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('import:run')
            ->setDescription('Manually run an import immediately')
            ->addArgument('import_name', InputArgument::REQUIRED, 'The import to run as defined in imports.xml');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode('crontab');
        } catch (LocalizedException $e) {
            //no-op
        }


        $this->manager->executeImportByName($input->getArgument('import_name'));
    }
}
