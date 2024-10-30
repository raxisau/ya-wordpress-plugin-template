<?php

namespace Jackbooted\DB;

use \Jackbooted\Config\Cfg;

/**
 * @copyright Confidential and copyright (c) 2024 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */
abstract class DAO extends \Jackbooted\Util\JB {

    private static $tableList = [];
    public static $log;
    public $db = null;
    public $primaryKey = '';
    public $tableName = '';
    public $tableStructure = '';
    public $keyFormat = '';
    public $orm = []; // Mapping of the variable to the column name. automatically adds 0 and 'id' => primaryKey
    public $titles = []; // Array of all the column titles Automatically replaces ID for primary key
    public $ignoreCols = [];

    /*
     * init must be implemented and should look something like this
     * public static function init() {
     *     self::$log = \Jackbooted\Util\Log4PHP::logFactory( __CLASS__ );
     * }
     */
    public static function init() {}

    public function __construct() {
        parent::__construct();
        if ( Cfg::get( 'jb_audit_tables', true ) ) {
            $this->auditTable();
        }

        if ( !isset( $this->orm[0] ) ) {
            $this->orm[0] = $this->primaryKey;
        }
        if ( !isset( $this->orm['id'] ) ) {
            $this->orm['id'] = $this->primaryKey;
        }

        if ( !isset( $this->titles[$this->primaryKey] ) ) {
            $this->titles[$this->primaryKey] = 'ID';
        }
    }

    public function defaultORM() {
        if ( ( $row = DB::oneRow( $this->db, "SELECT * FROM " . $this->tableName . " LIMIT 1" ) ) !== FALSE ) {
            foreach ( array_keys( $row ) as $idx => $colName ) {
                $this->orm[$colName] = $colName;
                $this->orm[$idx]     = $colName;
            }
        }
    }

    /**
     * @param  $row
     * @return mixed
     */
    public function getRowCount( $where = null, $params=null ) {
        $sql = 'SELECT COUNT(*) FROM ' . $this->tableName;
        $sql .= $this->toWhere( $where, $params );
        return DB::oneValue( $this->db, $sql, $params );
    }

    private function toWhere( $where, &$params = null ) {
        if ( is_array( $where ) && count( $where ) > 0 ) {
            $where = $this->objToRel( $where );
            $sql = '';
            if ( $params == null ) {
                $params = [];
            }
            foreach ( $where as $key => $value ) {

                // This allows for dummy columns to be part of the object without the
                // DAO automatically accessing them in the queries.
                if ( $this->ignoreCols != null && array_key_exists( $key, $this->ignoreCols ) ) {
                    continue;
                }

                if ( $sql == '' ) {
                    $sql .= ' WHERE ';
                }
                else {
                    $sql .= ' AND ';
                }

                if ( $value == null ) {
                    $sql .= $key . ' IS NULL';
                }
                else if ( stripos( $value, '%' ) !== false ) {
                    $sql .= $key . ' LIKE ?';
                    $params[] = $value;
                }
                else {
                    $sql .= $key . '=?';
                    $params[] = $value;
                }
            }

            return $sql;
        }
        else if ( is_string( $where ) ) {
            return ' WHERE ' . $where;
        }
        else {
            return '';
        }
    }

    /**
     * @param  $id
     * @param int $fetch
     * @return array
     */
    public function oneRow( $id, $fetch = DB::FETCH_ASSOC ) {
        if ( $id == '' ) {
            return false;
        }

        $sql = 'SELECT * FROM ' . $this->tableName . ' WHERE ' . $this->primaryKey . '=?';
        return DB::oneRow( $this->db, $sql, $id, $fetch );
    }

    /**
     * @param  $sets
     * @param  $where
     * @return int
     */
    public function update( $sets, $where ) {
        DBTable::clearCache();

        $sets = $this->objToRel( $sets );

        // This allows for dummy columns to be part of the object without the
        // DAO automatically accessing them in the queries.
        if ( $this->ignoreCols != null ) {
            foreach ( $this->ignoreCols as $ignoreCol ) {
                unset( $sets[$ignoreCol] );
            }
        }

        if ( count( $sets ) == 0 ) {
            return false;
        }

        $params = [];
        if ( ( $whereSql = $this->toWhere( $where, $params ) ) == '' ) {
            return false;
        }

        $sql = 'UPDATE ' . $this->tableName . ' ' .
                'SET ' . join( '=?, ', array_keys( $sets ) ) . '=? ' .
                $whereSql;

        $finalParams = array_merge( array_values( $sets ), $params );
        return DB::exec( $this->db, $sql, $finalParams );
    }

    /**
     * @param  $sets
     * @param  $where
     * @return int
     */
    public function search( $params=[], $fetch=DB::FETCH_ASSOC ) {

        $sql = 'SELECT';
        if ( !isset( $params['columns'] ) || $params['columns'] == null || $params['columns'] == '' ) {
            $sql .= ' *';
        }
        else if ( is_array( $params['columns'] ) ) {
            $sql .= ' ' . join( ',', array_keys( $this->objToRel( $params['columns'] ) ) );
        }
        else if ( is_string( $params['columns'] ) ) {
            $sql .= ' ' . $params['columns'];
        }

        $whereParams = null;
        $sql .= ' FROM ' . $this->tableName . $this->toWhere( $params['where'], $whereParams );

        if ( !isset( $params['order'] ) || $params['order'] == null || $params['order'] == '' ) {
            $sql .= '';
        }
        else if ( is_array( $params['order'] ) ) {
            $order = '';
            foreach ( $this->objToRel( $params['order'] ) as $key => $value ) {
                if ( $order != '' ) {
                    $order .= ', ';
                }
                $order .= $key;
                $value = strtoupper( $value );
                if ( $value == 'ASC' || $value == 'DESC' ) {
                    $order .= ' ' . $value;
                }
            }
            $sql .= ' ORDER BY ' . $order;
        }
        else if ( is_string( $params['order'] ) ) {
            if ( preg_match( '/^\s*ORDER BY\s+(.*)$/', $params['order'], $match ) ) {
                $order = $match[1];
            }
            else {
                $order = $params['order'];
            }
            $sql .= ' ORDER BY ' . $order;
        }

        if ( !isset( $params['limit'] ) || $params['limit'] == null || $params['limit'] == '' ) {
            $sql .= '';
        }
        else if ( is_array( $params['limit'] ) ) {
            $sql .= ' LIMIT ' . join( ',', $params['limit'] );
        }
        else if ( is_integer( $params['limit'] ) ) {
            $sql .= ' LIMIT ' . $params['limit'];
        }

        return new DBTable( $this->db, $sql, $whereParams, $fetch );
    }

    /**
     * @param  $row
     * @return int
     */
    public function delete( $row ) {

        // Get the Where part of the delete. Do not allow a delete with
        // no args or you will delete a whole table
        $params = null;
        $where = trim(  $this->toWhere( $row, $params ) );
        if ( $where == 'WHERE' || $where == '' ) {
            return false;
        }

        $sql = "DELETE FROM {$this->tableName} {$where}";

        return DB::exec( $this->db, $sql, $params );
    }

    public function commit() {
        DB::exec( $this->db, 'COMMIT' );
    }

    public function quote( $value ) {
        return DB::quote( $this->db, $value );
    }

    /**
     * @param  $row
     * @return bool|mixed
     */
    public function insert( $row, $insertMethod = 'INSERT' ) {
        $row = $this->objToRel( $row );

        // This allows for dummy columns to be part of the object without the
        // DAO automatically accessing them in the queries.
        if ( $this->ignoreCols != null ) {
            foreach ( $this->ignoreCols as $ignoreCol ) {
                unset( $row[$ignoreCol] );
            }
        }

        $jbDB = Cfg::get( 'jb_db', false ) && $this->db == DB::DEF;
        if ( $jbDB ) {
            if ( ! isset( $row[$this->primaryKey] ) ) {
                $pKey = DBMaintenance::dbNextNumber( $this->db, $this->tableName );
                $row[$this->primaryKey] = $pKey;
            }
            else {
                $pKey = $row[$this->primaryKey];
            }
        }

        $keys = array_keys( $row );
        $values = array_values( $row );

        $sql = $insertMethod . ' INTO ' . $this->tableName . ' (' . join( ',', $keys ) . ') VALUES (' . DB::in( $values ) . ')';
        if ( DB::exec( $this->db, $sql, $values ) != 1 ) {
            return false;
        }

        if ( !$jbDB ) {
            $pKey = DB::lastInsertId( $this->db );
        }

        return $pKey;
    }

    /**
     * @param  $object
     * @return array
     */
    public function objToRel( $object ) {
        $relational = [];
        if ( is_array( $object ) ) {
            foreach ( $object as $key => $val ) {
                if ( isset( $this->orm[$key] ) ) {
                    $key = $this->orm[$key];
                }
                $relational[$key] = $val;
            }
        }
        return $relational;
    }

    public function auditTable() {
        if ( in_array( null, [ $this->db, $this->tableName, $this->tableStructure ] ) ) {
            return false;
        }

        if ( !isset( self::$tableList[$this->db] ) ) {
            self::$tableList[$this->db] = array_flip( DBMaintenance::getTableList() );
        }

        if ( !isset( self::$tableList[$this->db][$this->tableName] ) ) {
            DB::exec( $this->db, $this->tableStructure );
            DBMaintenance::addTableToNextNumber( $this->tableName, $this->keyFormat, $this->tableName );
            self::$tableList[$this->db][$this->tableName] = 1;
        }

        return true;
    }

    public function __toString() {
        return $this->tableStructure;
    }
}
