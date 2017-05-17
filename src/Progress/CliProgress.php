<?php

namespace Jh\Import\Progress;

use Jh\Import\Config;
use Jh\Import\Source\Source;
use Symfony\Component\Console\Output\OutputInterface;
use TrashPanda\ProgressBarLog\ProgressBarLog;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CliProgress implements Progress
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var null|ProgressBarLog
     */
    private $progressBarLog;

    /**
     * @var int
     */
    private $numLogsToDisplay;


    public function __construct(OutputInterface $output, $numLogsToDisplay = 20)
    {
        $this->output = $output;
        $this->output->setDecorated(true);
        $this->numLogsToDisplay = $numLogsToDisplay;
    }

    /**
     * @param Source $source
     * @param Config $config
     * @return void
     */
    public function start(Source $source, Config $config)
    {
        $max = null;
        if ($source instanceof \Countable) {
            $max = $source->count();
        }

        $this->progressBarLog = new ProgressBarLog($this->numLogsToDisplay, $max);
        $this->progressBarLog->setOutput($this->output);

        $progressBar = $this->progressBarLog->getProgressBar();
        $progressBar->setBarCharacter('<fg=green>=</>');
        $progressBar->setProgressCharacter('<fg=green>></>');
        $progressBar->setBarWidth(100);
        $progressBar->setMessage('Import: ' . $config->getImportName(), 'title');

        $tPad    = str_repeat(' ', 40);
        $format  = "\n <bg=blue>$tPad</>\n <bg=blue> %title:-39s%</>\n <bg=blue>$tPad</>\n\n %current%/%max% %bar% ";
        $format .= "%percent:3s%%\n\n 🏁  <fg=blue>%remaining%</> remaining. Done <fg=blue>%elapsed%</> of estimated ";
        $format .= "<fg=blue>%estimated%</> (<info>%memory%</>)";

        $progressBar->setFormat($format);
        $progressBar->setRedrawFrequency(50);

        $this->progressBarLog->start();
    }

    public function advance()
    {
        $this->guardStarted();
        $this->progressBarLog->advance();
    }

    public function addLog(string $severity, string $message)
    {
        $this->guardStarted();
        $this->progressBarLog->addLog(strtolower($severity), $message);
    }

    public function finish(Source $source)
    {
        $this->guardStarted();
        $this->progressBarLog->finish();
    }

    private function guardStarted()
    {
        if (null === $this->progressBarLog) {
            throw new \RuntimeException('Progress not started');
        }
    }
}
