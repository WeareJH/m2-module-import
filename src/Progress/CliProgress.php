<?php

namespace Jh\Import\Progress;

use Countable;
use Jh\Import\Config;
use Jh\Import\Source\Source;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CliProgress implements Progress
{
    private int $totalLogCount = 0;
    private LoggerInterface $logger;
    private OutputInterface $output;
    private ?ProgressBar $progressBar;
    private FormatterHelper $formatterHelper;

    public function __construct(LoggerInterface $logger, OutputInterface $output, FormatterHelper $formatterHelper)
    {
        $this->output = $output;
        $this->logger = $logger;
        $this->formatterHelper = $formatterHelper;
    }

    public function start(Source $source, Config $config): void
    {
        $source instanceof Countable
            ? $max = $source->count()
            : $max = 0;

        $progressBar = new ProgressBar($this->output, (int) $max);

        ProgressBar::setPlaceholderFormatterDefinition(
            'total_log_count',
            function (ProgressBar $progressBar, OutputInterface $output) {
                return $this->getTotalLogCount();
            }
        );

        $progressBar->setBarCharacter('<fg=green>=</>');
        $progressBar->setProgressCharacter('<fg=green>></>');
        $progressBar->setBarWidth(100);
        $progressBar->setMessage('Import: ' . $config->getImportName(), 'title');
        $this->progressBar = $progressBar;

        $tPad = str_repeat(' ', 40);
        $format = "\n <bg=blue>$tPad</>\n <bg=blue> %title:-39s%</>\n <bg=blue>$tPad</>\n\n %current%/%max% %bar% ";
        $format .= "%percent:3s%%\n\n 🏁  <fg=blue>%remaining%</> remaining. Done <fg=blue>%elapsed%</> of estimated ";
        $format .= "<fg=blue>%estimated%</> (<info>%memory%</>)\n";
        $format .= "     Total messages <fg=blue>%total_log_count%</>\n";

        $progressBar->setFormat($format);
        $progressBar->setRedrawFrequency(50);

        $this->progressBar->start();
    }

    public function advance(): void
    {
        $this->guardStarted();
        $this->progressBar->advance();
    }

    public function addLog(string $severity, string $message): void
    {
        $this->output->writeln($this->formatterHelper->formatBlock($message, strtolower($severity)));
        $this->totalLogCount++;
        $this->logger->log($severity, $message);
    }

    public function finish(Source $source): void
    {
        $this->guardStarted();
        $this->progressBar->finish();

        $this->output->writeln([
            '',
            '<bg=blue>Import finished</>',
            ''
        ]);
    }

    private function guardStarted(): void
    {
        if (!$this->progressBar instanceof ProgressBar) {
            throw new RuntimeException('Progress not started');
        }
    }

    private function getTotalLogCount(): int
    {
        return $this->totalLogCount;
    }
}
