<?php

namespace Jh\Import\Source;

use Countable;
use Jh\Import\Report\Report;
use Generator;

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

    public static function fromCallable(callable $callable): self
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
        return new self($generator);
    }

    /**
     * The implementor should call the onSuccess callback with each row of data if it successfully parsed and validated
     * it. If parsing and validating failed - it should call the onError callback. All errors should be added to the
     * report.
     */
    public function traverse(callable $onSuccess, callable $onError, Report $report): void
    {
        foreach ($this->iterator as $i => $row) {
            $onSuccess($i, $row);
        }
    }

    public function count(): int
    {
        return iterator_count($this->iterator);
    }

    /**
     * An ID which represents this particular import - For example a file type source should return the
     * same ID for the same file.
     */
    public function getSourceId(): string
    {
        return spl_object_hash($this->iterator);
    }
}
