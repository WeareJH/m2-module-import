<?php

declare(strict_types=1);

namespace Jh\Import\Output;

use Magento\Framework\App\State;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Factory
{
    /**
     * @var State
     */
    private $appState;

    public function __construct(State $appState, OutputInterface $output)
    {
        $this->appState = $appState;
        $this->output = $output;
    }

    public function get(): OutputInterface
    {
        if ($this->appState->getMode() === State::MODE_DEVELOPER || PHP_SAPI === 'cli') {
            return $this->getConsoleOutput();
        }

        return new NullOutput();
    }

    public function getConsoleOutput(): ConsoleOutput
    {
        return $this->output;
    }
}
