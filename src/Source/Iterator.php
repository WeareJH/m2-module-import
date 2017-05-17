<?php

namespace Jh\Import\Source;

use Countable;
use Jh\Import\Report\Report;
use Generator;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Iterator implements Source, Countable
{

    /**
     * @var \Iterator
     */
    private $iterator;

    public function __construct(\Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    public static function fromCallable(callable $callable) : self
    {
        $generator = $callable();
        if (!$generator instanceof Generator) {
            throw new \InvalidArgumentException(
                sprintf(
                    'callable must return an instance of Generator, got "%s"',
                    is_object($generator) ? get_class($generator) : gettype($generator)
                )
            );
        }
        return new static($generator);
    }

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
    public function traverse(callable $onSuccess, callable $onError, Report $report)
    {
        foreach ($this->iterator as $i => $row) {
            $onSuccess($i, $row);
        }
    }

    public function count()
    {
        return iterator_count($this->iterator);
    }

    /**
     * An ID which represents this particular import - For example a file type source should return the
     * same ID for the same file.
     *
     * @return string
     */
    public function getSourceId()
    {
        return spl_object_hash($this->iterator);
    }
}
