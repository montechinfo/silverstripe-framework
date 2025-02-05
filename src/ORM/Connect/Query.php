<?php

namespace SilverStripe\ORM\Connect;

use SilverStripe\Core\Convert;
use Iterator;

/**
 * Abstract query-result class. A query result provides an iterator that returns a map for each record of a query
 * result.
 *
 * The map should be keyed by the column names, and the values should use the following types:
 *
 *  - boolean returned as integer 1 or 0 (to ensure consistency with MySQL that doesn't have native booleans)
 *  - integer types returned as integers
 *  - floating point / decimal types returned as floats
 *  - strings returned as strings
 *  - dates / datetimes returned as strings
 *
 * Note that until SilverStripe 4.3, bugs meant that strings were used for every column type.
 *
 * Once again, this should be subclassed by an actual database implementation.  It will only
 * ever be constructed by a subclass of SS_Database.  The result of a database query - an iteratable object
 * that's returned by DB::SS_Query
 *
 * Primarily, the Query class takes care of the iterator plumbing, letting the subclasses focusing
 * on providing the specific data-access methods that are required: {@link nextRecord()}, {@link numRecords()}
 * and {@link seek()}
 */
abstract class Query implements Iterator
{

    /**
     * The current record in the iterator.
     *
     * @var array
     */
    protected $currentRecord = null;

    /**
     * The number of the current row in the iterator.
     *
     * @var int
     */
    protected $rowNum = -1;

    /**
     * Flag to keep track of whether iteration has begun, to prevent unnecessary seeks
     *
     * @var bool
     */
    protected $queryHasBegun = false;

    /**
     * Return an array containing all the values from a specific column. If no column is set, then the first will be
     * returned
     *
     * @param string $column
     * @return array
     */
    public function column($column = null)
    {
        $result = [];

        while ($record = $this->next()) {
            if ($column) {
                $result[] = $record[$column];
            } else {
                $result[] = $record[key($record)];
            }
        }

        return $result;
    }

    /**
     * Return an array containing all values in the leftmost column, where the keys are the
     * same as the values.
     *
     * @return array
     */
    public function keyedColumn()
    {
        $column = [];
        foreach ($this as $record) {
            $val = $record[key($record)];
            $column[$val] = $val;
        }
        return $column;
    }

    /**
     * Return a map from the first column to the second column.
     *
     * @return array
     */
    public function map()
    {
        $column = [];
        foreach ($this as $record) {
            $key = reset($record);
            $val = next($record);
            $column[$key] = $val;
        }
        return $column;
    }

    /**
     * Returns the next record in the iterator.
     *
     * @return array
     */
    public function record()
    {
        return $this->next();
    }

    /**
     * Returns the first column of the first record.
     *
     * @return string
     */
    public function value()
    {
        $record = $this->next();
        if ($record) {
            return $record[key($record)];
        }
        return null;
    }

    /**
     * Return an HTML table containing the full result-set
     *
     * @return string
     */
    public function table()
    {
        $first = true;
        $result = "<table>\n";

        foreach ($this as $record) {
            if ($first) {
                $result .= "<tr>";
                foreach ($record as $k => $v) {
                    $result .= "<th>" . Convert::raw2xml($k) . "</th> ";
                }
                $result .= "</tr> \n";
            }

            $result .= "<tr>";
            foreach ($record as $k => $v) {
                $result .= "<td>" . Convert::raw2xml($v) . "</td> ";
            }
            $result .= "</tr> \n";

            $first = false;
        }
        $result .= "</table>\n";

        if ($first) {
            return "No records found";
        }
        return $result;
    }

    /**
     * Iterator function implementation. Rewind the iterator to the first item and return it.
     * Makes use of {@link seek()} and {@link numRecords()}, takes care of the plumbing.
     *
     * @return void
     */
    public function rewind()
    {
        if ($this->queryHasBegun && $this->numRecords() > 0) {
            $this->queryHasBegun = false;
            $this->currentRecord = null;
            $this->seek(0);
        }
    }

    /**
     * Iterator function implementation. Return the current item of the iterator.
     *
     * @return array
     */
    public function current()
    {
        if (!$this->currentRecord) {
            return $this->next();
        } else {
            return $this->currentRecord;
        }
    }

    /**
     * Iterator function implementation. Return the first item of this iterator.
     *
     * @return array
     */
    public function first()
    {
        $this->rewind();
        return $this->current();
    }

    /**
     * Iterator function implementation. Return the row number of the current item.
     *
     * @return int
     */
    public function key()
    {
        return $this->rowNum;
    }

    /**
     * Iterator function implementation. Return the next record in the iterator.
     * Makes use of {@link nextRecord()}, takes care of the plumbing.
     *
     * @return array
     */
    public function next()
    {
        $this->queryHasBegun = true;
        $this->currentRecord = $this->nextRecord();
        $this->rowNum++;
        return $this->currentRecord;
    }

    /**
     * Iterator function implementation. Check if the iterator is pointing to a valid item.
     *
     * @return bool
     */
    public function valid()
    {
        if (!$this->queryHasBegun) {
            $this->next();
        }
        return $this->currentRecord !== false;
    }

    /**
     * Return the next record in the query result.
     *
     * @return array
     */
    abstract public function nextRecord();

    /**
     * Return the total number of items in the query result.
     *
     * @return int
     */
    abstract public function numRecords();

    /**
     * Go to a specific row number in the query result and return the record.
     *
     * @param int $rowNum Row number to go to.
     * @return array
     */
    abstract public function seek($rowNum);
}
