<?php

namespace Jackbooted\DB;

use \Jackbooted\DB\DB;
use \Jackbooted\Html\Widget;
use \Jackbooted\Util\DataCache;
use \Jackbooted\Util\Invocation;

/**
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */

/**
 * This wraps the database fetch into 2D array of values.
 *
 * Allows the user to chain together commans without crashing.
 * eg. getColumn will return an empty array even on bad result set. This
 * will allow you to call array based functions without error
 *
 * Examples of use:
 * <pre>
 * echo Lists::select ( DBTable::factory ( 'reg', 'SHOW_TABLES' )->getColumn( 0 ) );
 * </pre>
 *
 * For more examples
 * @see classtest_DB_Base
 * @see classtest_DB_StressTest
 * @see DB
 */
class DBTable extends \Jackbooted\Util\JB implements \Iterator {

    private static $dataCache;

    public static function init() {
        self::$dataCache = new DataCache( __CLASS__, 100 );
    }

    /**
     * Use a static creator so that you can chain the methods together.
     *
     * @param object $resultSet PDO Result set.
     *
     * @since 1.0
     * @return DBTable
     */
    public static function create( $dbh, $qry, $params = null, $fetch = DB::FETCH_BOTH ) {
        return new DBTable( $dbh, $qry, $params, $fetch );
    }

    public static function factory( $dbh, $qry, $params = null, $fetch = DB::FETCH_BOTH ) {
        return new DBTable( $dbh, $qry, $params, $fetch );
    }

    public static function clearCache() {
        self::$dataCache->clear();
    }

    // Might need a value to return
    private static $emptyArray = [];
    private static $falseValue = false;
    // The array in memory
    private $table = null;
    private $fetch;

    /**
     * Construct a table in memory.
     *
     * @param object $resultSet Pass n result set from PDO query.
     *
     * @since 1.0
     */
    public function __construct( $dbh, $qry, $params = null, $fetch = DB::FETCH_BOTH ) {
        parent::__construct();
        $this->fetch = $fetch;

        if ( is_object( $dbh ) ) {
            $this->table = $dbh->fetchAll( $fetch );
        }
        else {
            $cacheKey = $dbh . ' ' . $qry . ' ' . serialize( $params );
            if ( ( $cacheValue = self::$dataCache->get( $cacheKey ) ) !== false ) {
                $this->table = $cacheValue;
            }
            else {
                if ( ( $resultSet = DB::query( $dbh, $qry, $params ) ) === false )
                    return;
                $this->table = $resultSet->fetchAll( $fetch );
                self::$dataCache->set( $cacheKey, $this->table );
            }
        }
    }

    /**
     * Gets a column.
     *
     * @param mixed $columnNameOrIndex Index or the name of the column.
     *
     * @since 1.0
     * @return array
     */
    public function getColumn( $columnNameOrIndex = 0 ) {
        if ( !$this->ok() ) {
            return [];
        }

        $column = [];
        foreach ( $this->table as &$row ) {
            $column[] = $row[$columnNameOrIndex];
        }

        return $column;
    }

    /**
     * Gets a column.
     *
     * @param mixed $columnNameOrIndex Index or the name of the column.
     *
     * @since 1.0
     * @return array
     */
    public function getColumnCount() {
        if ( !$this->ok() ) {
            return 0;
        }

        return count( $this->getRow( 0 ) );
    }

    /**
     * Get a pointer to the array.
     *
     * @since 1.0
     * @return array
     */
    public function &getRaw() {
        if ( !$this->ok() ) {
            return self::$emptyArray;
        }

        return $this->table;
    }

    /**
     * Sets one value.
     *
     * @param mixed $value The value to set.
     * @param mixed $columnNameOrIndex Which column do you want.
     * @param integer $idx Which row do you want.
     *
     * @since 1.0
     * @return mixed
     */
    public function setValue( $value, $columnNameOrIndex = 0, $row = 0 ) {
        if ( !$this->ok() ) {
            return self::$falseValue;
        }

        $this->table[$row][$columnNameOrIndex] = $value;
    }

    /**
     * Gets one value.
     *
     * @param mixed $columnNameOrIndex Which column do you want.
     * @param integer $idx Which row do you want.
     *
     * @since 1.0
     * @return array
     */
    public function &getValue( $columnNameOrIndex = 0, $row = 0 ) {
        if ( !$this->ok() ) {
            return self::$falseValue;
        }

        $row = $this->getRow( $row );

        if ( is_integer( $columnNameOrIndex ) && !isset( $row[$columnNameOrIndex] ) ) {
            $row = array_values( $row );
        }

        return $row[$columnNameOrIndex];
    }

    /**
     * Gets one row.
     *
     * @param integer $idx Which row do you want.
     *
     * @since 1.0
     * @return array
     */
    public function &getRow( $idx = 0 ) {
        if ( !$this->ok() || $idx < 0 || $idx >= $this->getRowCount() ) {
            return self::$emptyArray;
        }

        return $this->table[$idx];
    }

    /**
     * Gets raw table.
     *
     * @since 1.0
     * @return array
     */
    public function &getTable() {
        if ( !$this->ok() ) {
            $ret = null;
            return $ret;
        }

        return $this->table;
    }

    /**
     * Gets the number of rows.
     *
     * @since 1.0
     * @return integer
     */
    public function getRowCount() {
        if ( !$this->ok() ) {
            return 0;
        }

        return count( $this->table );
    }

    /**
     * Checks to see if the table was correctly read into memory.
     *
     * @since 1.0
     * @return boolean
     */
    public function ok() {
        return is_array( $this->table );
    }

    /**
     * Checks to see if the table was correctly read into memory.
     *
     * @since 1.0
     * @return boolean
     */
    public function isEmpty() {
        return $this->getRowCount() <= 0;
    }

    // Pointer to the current row
    private $currentRow = 0;

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return array
     */
    public function &current() {
        return $this->getRow( $this->currentRow );
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return integer
     */
    public function key() {
        return $this->currentRow;
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return void
     */
    public function next() {
        ++$this->currentRow;
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return void
     */
    public function rewind() {
        $this->currentRow = 0;
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return boolean
     */
    public function valid() {
        return $this->currentRow < $this->getRowCount();
    }

    public function __toString() {
        $id = 'DBTable_' . Invocation::next();

        $msg = '<table id="' . $id . '">';
        if ( $this->getRowCount() == 0 ) {
            $msg .= '<tr><td>No Rows</td></tr>';
        }
        else {
            $firstTime = true;
            foreach ( $this->table as &$row ) {
                if ( $firstTime ) {
                    $msg .= '  <tr>';
                    foreach ( $row as $key => &$value ) {
                        $msg .= '<th>' . $key . '</th>';
                    }
                    $msg .= '  </tr>' . "\n";
                    $firstTime = false;
                }
                $msg .= '  <tr>';
                foreach ( $row as &$value ) {
                    $msg .= '<td>' . $value . '</td>';
                }
                $msg .= '  </tr>' . "\n";
            }
        }
        $msg .= '</table>' . "\n";
        return Widget::styleTable( '#' . $id ) . $msg;
    }

}
