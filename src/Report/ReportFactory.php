<?php

namespace Jh\Import\Report;

use Jh\Import\Config;
use Jh\Import\LogLevel;
use Jh\Import\Report\Handler\ConsoleHandler;
use Jh\Import\Report\Handler\DatabaseHandler;
use Jh\Import\Report\Handler\Handler;
use Jh\Import\Source\Source;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class ReportFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createFromSourceAndConfig(Source $source, Config $config)
    {
        $handlers = [
            $this->objectManager->get(DatabaseHandler::class)
        ];

        $appState = $this->objectManager->get(State::class);

        if ($appState->getMode() === State::MODE_DEVELOPER || PHP_SAPI === 'cli') {
            $handlers[] = $this->objectManager->create(ConsoleHandler::class, [
                'minErrorLevel' => LogLevel::WARNING
            ]);
        }

        foreach ($config->getReportHandlers() as $reportHandler) {
            $reportHandler = $this->objectManager->get($reportHandler);

            if (!$reportHandler instanceof Handler) {
                throw new \RuntimeException(sprintf('Report handler must implement "%s"', Handler::class));
            }

            $handlers[] = $reportHandler;
        }

        return new Report($handlers, $config->getImportName(), $source->getSourceId());
    }
}
