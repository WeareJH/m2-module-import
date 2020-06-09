<?php

namespace Jh\Import\Source;

use Jh\Import\Report\Report;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Csv implements Source, \Countable
{
    /**
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var string
     */
    private $delimiter;

    /**
     * @var string
     */
    private $enclosure;

    /**
     * @var string
     */
    private $escape;

    /**
     * @var string
     */
    private $sourceId;

    public function __construct(string $file, string $delimiter = ',', string $enclosure = '"', string $escape = '\\')
    {
        $this->file = new \SplFileObject($file, 'r');
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;

        $delimiterMap = [
            '\n' => "\n",
            '\t' => "\t",
        ];

        if (isset($delimiterMap[$this->delimiter])) {
            $this->delimiter = $delimiterMap[$this->delimiter];
        }

        $this->sourceId = md5_file($this->file->getRealPath());
    }

    public function traverse(callable $onSuccess, callable $onError, Report $report)
    {
        $headers = $this->getHeader();

        foreach ($this->filterInvalidRows($this->readLines(), $headers, $report, $onError) as $rowNumber => $row) {
            $onSuccess($this->file->key() + 1, array_combine($headers, $row));
        }
    }

    private function filterInvalidRows(
        \Generator $generator,
        array $headers,
        Report $report,
        callable $onError
    ) : \Generator {
        foreach ($generator as $rowNumber => $row) {
            if (!$this->validateRow($headers, $rowNumber, $row, $report)) {
                $onError($rowNumber);
                continue;
            }

            yield $rowNumber => $row;
        }
    }

    private function readLines() : \Generator
    {
        while (!$this->file->eof() && ($row = $this->getRow()) && $row[0] !== null) {
            yield $this->file->key() => $row;
        }
    }

    private function getHeader(): array
    {
        return str_getcsv(
            rtrim(rtrim($this->file->fgets(), "\r\n"), ','),
            $this->delimiter,
            $this->enclosure,
            $this->escape
        );
    }

    private function getRow(): array
    {
        return $this->file->fgetcsv($this->delimiter, $this->enclosure, $this->escape);
    }

    private function validateRow(array $headers, int $rowNumber, array $row, Report $report)
    {
        if (count($row) !== count($headers)) {
            $report->addError(sprintf('Column count does not match header count on row: "%d"', $rowNumber));
            return false;
        }
        return true;
    }

    public function count() : int
    {
        $currentKey = $this->file->key();
        $this->file->seek(PHP_INT_MAX);
        $numLines = $this->file->key();
        $this->file->seek($currentKey);
        return $numLines;
    }

    public function getFile() : \SplFileObject
    {
        return $this->file;
    }

    /**
     * An ID which represents this particular import - For example a file type source should return the
     * same ID for the same file.
     *
     * @return string
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }
}
