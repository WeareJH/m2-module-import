<?php

namespace Jh\ImportTest\Progress;

use Jh\Import\Progress\CliProgress;
use Jh\Import\Source\Source;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class CliProgressTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Progress not started
     */
    public function testAdvanceThrowsExceptionIfNotStarted()
    {
        $output = $this->prophesize(OutputInterface::class);

        $progress = new CliProgress($output->reveal());
        $progress->advance();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Progress not started
     */
    public function testFinishThrowsExceptionIfNotStarted()
    {
        $output = $this->prophesize(OutputInterface::class);
        $source = $this->prophesize(Source::class);

        $progress = new CliProgress($output->reveal());
        $progress->finish($source->reveal());
    }
}
