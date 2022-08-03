<?php

namespace Jh\ImportTest\Progress;

use Jh\Import\Progress\CliProgress;
use Jh\Import\Source\Source;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CliProgressTest extends TestCase
{
    public function testAdvanceThrowsExceptionIfNotStarted(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Progress not started');

        $output = $this->prophesize(OutputInterface::class);

        $progress = new CliProgress($output->reveal());
        $progress->advance();
    }

    public function testFinishThrowsExceptionIfNotStarted(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Progress not started');

        $output = $this->prophesize(OutputInterface::class);
        $source = $this->prophesize(Source::class);

        $progress = new CliProgress($output->reveal());
        $progress->finish($source->reveal());
    }
}
