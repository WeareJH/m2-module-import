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
     */
    public function traverse(callable $onSuccess, callable $onError, Report $report): void;

    /**
     * An ID which represents this particular import - For example a file type source should return the
     * same ID for the same file.
     */
    public function getSourceId(): string;
}
