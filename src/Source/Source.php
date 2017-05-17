<?php

namespace Jh\Import\Source;

use Jh\Import\Report\Report;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
interface Source
{
    /**
     * The implementor should call the onSuccess callback with each row of data if it successfully parsed and validated
     * it. If parsing and validating failed - it should call the onError callback. All errors should be added to the
     * report.
     *
     * @param callable $onSuccess
     * @param callable $onError
     * @param Report $report
     * @return void
     */
    public function traverse(callable $onSuccess, callable $onError, Report $report);

    /**
     * An ID which represents this particular import - For example a file type source should return the
     * same ID for the same file.
     *
     * @return string
     */
    public function getSourceId();
}
