<?php
declare(strict_types=1);

namespace Jh\Import\Source;

use Countable;
use DOMElement;
use Jh\Import\Report\Report;
use RuntimeException;
use XMLReader;

use function md5_file;
use function sprintf;

/**
 * Streams a local XML file with XMLReader rather than loading the whole document into memory -
 * same reasoning as Csv using SplFileObject line-by-line instead of file_get_contents. Every
 * element matching $recordElement, at any depth, becomes one row; that element's direct child
 * elements become the row's field => value map (tag name => text content). Attributes and nested
 * elements below the immediate children aren't mapped - a feed needing those is a new sibling
 * Source, not a reason to grow this one.
 */
class Xml implements Source, Countable
{
    private string $file;
    private string $recordElement;
    private string $sourceId;
    private ?int $count = null;

    public function __construct(string $file, string $recordElement)
    {
        $this->file = $file;
        $this->recordElement = $recordElement;
        $this->sourceId = md5_file($file);
    }

    public function traverse(callable $onSuccess, callable $onError, Report $report): void
    {
        $reader = $this->openReader();
        $rowNumber = 0;

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== $this->recordElement) {
                continue;
            }

            $rowNumber++;
            $row = $this->readRecord($reader);

            if ($row === null) {
                $onError($rowNumber);
                $report->addError(sprintf('Could not read record %d as "%s"', $rowNumber, $this->recordElement));
                continue;
            }

            $onSuccess($rowNumber, $row);
        }

        $reader->close();
    }

    /**
     * @return array<string, string>|null
     */
    private function readRecord(XMLReader $reader): ?array
    {
        $node = $reader->expand();

        if (!$node instanceof DOMElement) {
            return null;
        }

        $row = [];

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $row[$child->localName] = $child->textContent;
            }
        }

        return $row;
    }

    public function count(): int
    {
        if ($this->count !== null) {
            return $this->count;
        }

        $reader = $this->openReader();
        $count = 0;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === $this->recordElement) {
                $count++;
            }
        }

        $reader->close();

        return $this->count = $count;
    }

    /**
     * Same reasoning as Csv::getSourceId() - hashes file content, so the same content is always
     * detected as a duplicate/already-imported regardless of filename.
     */
    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    private function openReader(): XMLReader
    {
        $reader = new XMLReader();

        if (!$reader->open($this->file)) {
            throw new RuntimeException(sprintf('Could not open XML file "%s"', $this->file));
        }

        return $reader;
    }
}
