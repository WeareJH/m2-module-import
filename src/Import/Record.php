<?php

namespace Jh\Import\Import;

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
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

    /**
     * @param int $rowNumber
     * @param array $data
     */
    public function __construct(int $rowNumber, array $data = [])
    {
        $this->rowNumber = $rowNumber;
        $this->data = $data;
    }

    public function getRowNumber() : int
    {
        return $this->rowNumber;
    }

    /**
     * @param string $columnName
     * @param mixed $value
     * @return void
     */
    public function setColumnValue(string $columnName, $value)
    {
        $this->data[$columnName] = $value;
    }

    /**
     * @param string $columnName
     * @return void
     */
    public function unset(string $columnName)
    {
        unset($this->data[$columnName]);
    }

    /**
     * @param string[] ...$columnNames
     * @return void
     */
    public function unsetMany(string ...$columnNames)
    {
        foreach ($columnNames as $columnName) {
            $this->unset($columnName);
        }
    }

    /**
     * @param string $columnName
     * @param mixed $default
     * @param string|null $dataType The expected data type of the value
     * @return mixed
     * @throws \RuntimeException
     */
    public function
    getColumnValue(string $columnName, $default = null, $dataType = null)
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

    /**
     * @param string $columnName
     * @param null $default
     * @param string|null $dataType
     * @throws \RuntimeException
     * @return mixed
     */
    public function getColumnValueAndUnset(string $columnName, $default = null, $dataType = null)
    {
        $value = $this->getColumnValue($columnName, $default, $dataType);
        $this->unset($columnName);
        return $value;
    }

    /**
     * @param string $columnName
     * @return bool
     */
    public function columnExists(string $columnName)
    {
        return array_key_exists($columnName, $this->data);
    }

    /**
     * @return array
     */
    public function asArray() : array
    {
        return $this->data;
    }

    /**
     * @param string $column
     * @param callable $callable
     * @return void
     */
    public function transform(string $column, callable $callable)
    {
        $this->setColumnValue($column, $callable($this->getColumnValue($column)));
    }

    /**
     * @param string $columnFrom
     * @param string $columnTo
     * @return void
     */
    public function renameColumn(string $columnFrom, string $columnTo)
    {
        $this->setColumnValue($columnTo, $this->getColumnValue($columnFrom));
        $this->unset($columnFrom);
    }

    /**
     * @param string $columnFrom
     * @param string $columnTo
     * @param string $key
     * @return void
     */
    public function moveColumnToArray(string $columnFrom, string $columnTo, string $key = null)
    {
        if (null === $key) {
            $key = $columnFrom;
        }

        $array = $this->getColumnValue($columnTo, []);
        $array[$key] = $this->getColumnValue($columnFrom);
        $this->setColumnValue($columnTo, $array);
        $this->unset($columnFrom);
    }

    /**
     * @param string[] $columns
     * @param string $columnTo
     * @return void
     */
    public function moveMultipleColumnsToArray(array $columns, string $columnTo)
    {
        foreach ($columns as $columnFrom) {
            $this->moveColumnToArray($columnFrom, $columnTo);
        }
    }

    /**
     * @param string $column
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function addValueToArray(string $column, string $key, $value)
    {
        $array = $this->getColumnValue($column, [], 'array');
        $array[$key] = $value;
        $this->setColumnValue($column, $array);
    }
}
