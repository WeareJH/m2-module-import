<?php

namespace Jh\Import\Import;

class Record
{
    /**
     * @var int
     */
    private $rowNumber;

    /**
     * @var array
     */
    private $data;

    public function __construct(int $rowNumber, array $data = [])
    {
        $this->rowNumber = $rowNumber;
        $this->data = $data;
    }

    public function getRowNumber(): int
    {
        return $this->rowNumber;
    }

    public function setColumnValue(string $columnName, $value): void
    {
        $this->data[$columnName] = $value;
    }

    public function unset(string $columnName): void
    {
        unset($this->data[$columnName]);
    }

    public function unsetMany(string ...$columnNames): void
    {
        foreach ($columnNames as $columnName) {
            $this->unset($columnName);
        }
    }

    public function only(string ...$columnNames): void
    {
        $data = [];
        foreach ($columnNames as $columnName) {
            $data[$columnName] = $this->getColumnValue($columnName);
        }

        $this->data = $data;
    }

    public function getColumnValue(string $columnName, $default = null, string $dataType = null)
    {
        $value = $this->data[$columnName] ?? $default;

        if ($dataType && gettype($value) !== $dataType) {
            throw new \RuntimeException(
                sprintf(
                    'Value of "%s" data type: "%s" does not match expected: "%s"',
                    $columnName,
                    gettype($value),
                    $dataType
                )
            );
        }

        return $value;
    }

    public function getColumnValueAndUnset(string $columnName, $default = null, string $dataType = null)
    {
        $value = $this->getColumnValue($columnName, $default, $dataType);
        $this->unset($columnName);
        return $value;
    }

    public function columnExists(string $columnName): bool
    {
        return array_key_exists($columnName, $this->data);
    }

    public function asArray(): array
    {
        return $this->data;
    }

    public function transform(string $column, callable $callable): void
    {
        $this->setColumnValue($column, $callable($this->getColumnValue($column)));
    }

    public function renameColumn(string $columnFrom, string $columnTo): void
    {
        $this->setColumnValue($columnTo, $this->getColumnValue($columnFrom));
        $this->unset($columnFrom);
    }

    public function moveColumnToArray(string $columnFrom, string $columnTo, string $key = null): void
    {
        if (null === $key) {
            $key = $columnFrom;
        }

        $array = $this->getColumnValue($columnTo, []);
        $array[$key] = $this->getColumnValue($columnFrom);
        $this->setColumnValue($columnTo, $array);
        $this->unset($columnFrom);
    }

    public function moveMultipleColumnsToArray(array $columns, string $columnTo): void
    {
        foreach ($columns as $columnFrom) {
            $this->moveColumnToArray($columnFrom, $columnTo);
        }
    }

    public function addValueToArray(string $column, string $key, $value): void
    {
        $array = $this->getColumnValue($column, [], 'array');
        $array[$key] = $value;
        $this->setColumnValue($column, $array);
    }
}
