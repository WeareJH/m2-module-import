<?php

namespace Jh\Import\Report;

use Jh\Import\LogLevel;
use Jh\Import\Progress\CliProgress;
use Jh\Import\Report\Handler\ConsoleHandler;
use Jh\Import\Report\Handler\DatabaseHandler;
use Jh\Import\Source\Source;
use Magento\Framework\App\State;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ReportFactory
{
    /**
     * @var State
     */
    private $appState;
    /**
     * @var DatabaseHandler
     */
    private $databaseHandler;
    /**
     * @var CliProgress
     */
    private $cliProgress;

    public function __construct(State $appState, DatabaseHandler $databaseHandler, CliProgress $cliProgress)
    {
        $this->appState = $appState;
        $this->databaseHandler = $databaseHandler;
        $this->cliProgress = $cliProgress;
    }

    public function createFromSourceAndName(Source $source, string $importName)
    {
        $handlers = [
            $this->databaseHandler
        ];

        if ($this->appState->getMode() === State::MODE_DEVELOPER || posix_isatty(STDOUT)) {
            $handlers[] = new ConsoleHandler($this->cliProgress, LogLevel::WARNING);
        }

        return new Report($handlers, $importName, $source->getSourceId());
    }
}
