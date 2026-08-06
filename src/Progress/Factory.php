<?php

declare(strict_types=1);

namespace Jh\Import\Progress;

use Magento\Framework\App\State;

class Factory
{
    /**
     * @var CliProgress
     */
    private $cliProgress;

    /**
     * @var State
     */
    private $appState;

    public function __construct(State $appState, CliProgress $cliProgress)
    {
        $this->appState = $appState;
        $this->cliProgress = $cliProgress;
    }

    public function get(): Progress
    {
        if ($this->appState->getMode() === State::MODE_DEVELOPER || $this->isInteractiveCliOutput()) {
            return $this->getCliProgress();
        }

        return new NullProgress();
    }

    private function isInteractiveCliOutput(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }

    public function getCliProgress(): CliProgress
    {
        return $this->cliProgress;
    }
}
