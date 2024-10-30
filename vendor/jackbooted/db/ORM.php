<?php

namespace Jackbooted\DB;

use \Jackbooted\Forms\Request;

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

/**
 */
class ORM extends \Jackbooted\Util\JB {

    const UPDATE = 'update';
    const INSERT = 'insert';

    /*
     * init must be implemented and should look something like this
     * public static function init() {
     *     self::$log = \Jackbooted\Util\Log4PHP::logFactory( __CLASS__ );
     *     self::$dao = new EmailQueueDAO ();
     * }
     */
    public static function init() {}

    /*
     * Factory must be implenebted and should look something like this:
     * public static function factory( $data ) {
     *     return new EmailQueue( $data );
     * }
     */
    public static function factory( $data ) {
        $clazz = static::class;
        return new $clazz( $data );
    }

    public static function create( $data ) {
        $obj = static::factory( $data );

        // remove the primary key from the update
        if ( isset( $obj->data[$obj->dao->primaryKey] ) ) {
            unset( $obj->data[$obj->dao->primaryKey] );
        }
        $obj->save();
        return $obj;
    }

    public static function load ( $id ) {
        if ( ! is_numeric( $id ) ) {
            return false;
        }

        $obj = static::factory( [] );

        if ( ( $row = $obj->dao->oneRow( $id ) ) === false ) {
            return false;
        }

        $obj->data = $obj->dao->objToRel( $row );
        $obj->clearDirty();
        return $obj;
    }

    protected static function tableToObjectList( $table ) {
        $objList = [];
        foreach ( $table as $row ) {
            $obj = static::factory( $row );
            $objList[$obj->id] = $obj;
        }
        return $objList;
    }

    public static function sqlComment( $function, $file, $line ) {
        return '/* ' . $function . ' ' . basename( $file ) . ' ' . $line . ' */';
    }

    protected $data;
    protected $dirty;
    private   $dao;

    public function __construct( DAO $dao, $data ) {
        parent::__construct();
        $this->dao = $dao;
        $this->data = $this->dao->objToRel( $data );
        $this->clearDirty();
    }

    public function clearDirty() {
        $this->dirty = array_fill_keys( array_keys( $this->data ), 0 );
    }

    public function __get( $key ) {
        if ( isset( $this->dao->orm[$key] ) ) {
            $key = $this->dao->orm[$key];
        }

        if ( ! isset( $this->data[$key] ) ) {
            //echo "Trying to access key: $key and it does not appear\n";
            //print_r( $this->dao->orm );
            //print_r( debug_backtrace( ) );
            return '';
        }

        return $this->data[$key];
    }

    public function __set( $key, $value ) {
        if ( isset( $this->dao->orm[$key] ) ) {
            $key = $this->dao->orm[$key];
        }

        if ( ! isset( $this->data[$key] ) || $this->data[$key] != $value ) {
            $this->dirty[$key] = 1; // Set this to be updated
            $this->data[$key] = $value;
        }
    }

    public function getData() {
        return $this->data;
    }

    public function save() {
        if ( isset( $this->data[$this->dao->primaryKey] ) ) {
            $where = [ $this->dao->primaryKey => $this->data[$this->dao->primaryKey] ];
            $data = array_merge( $this->data );

            // remove the primary key from the update
            unset( $data[$this->dao->primaryKey] );

            // Do not update any values that have not been changed
            foreach ( $this->dirty as $key => $value ) {
                if ( $value != 1 )
                    unset( $data[$key] );
            }

            $this->dao->update( $data, $where );
            $this->clearDirty();
            return self::UPDATE;
        }
        else {
            return ( $this->insert() === false ) ? false : self::INSERT;
        }
    }

    public function insert() {
        if ( ( $pKey = $this->dao->insert( $this->data ) ) === false ) {
            return false;
        }

        if ( ! isset( $this->data[$this->dao->primaryKey] ) ) {
            $this->data[$this->dao->primaryKey] = $pKey;
        }
        $this->clearDirty();

        return $pKey;
    }

    public function delete() {
        if ( isset( $this->data[$this->dao->primaryKey] ) ) {
            return $this->dao->delete( [ $this->dao->primaryKey => $this->data[$this->dao->primaryKey] ] );
        }
        else {
            return $this->dao->delete( $this->data );
        }
    }

    public function copyToRequest() {
        foreach ( $this->getData() as $key => $value ) {
            Request::set( $key, $value );
        }
        return $this;
    }

    public function copyFromRequest( ) {
        $formVars = Request::get();

        foreach ( $this->getData() as $key => $value ) {
            if ( ! isset( $formVars[$key] ) ) continue;
            if ( $formVars[$key] == $value ) continue;

            $this->$key = $formVars[$key];
        }
        return $this;
    }

    public function commit() {
        $this->dao->commit();
    }

    public function equals(self $other): bool {
        $match = true;
        foreach ( $this->getData() as $key => $val ) {
            if ( $val != $other->$key ) {
                $match = false;
                break;
            }
        }
        return $match;
    }

    public function refresh() {
        if ( ! isset( $this->data[$this->dao->primaryKey] ) ) {
            return $this;
        }

        if ( ( $row = $this->dao->oneRow( $this->data[$this->dao->primaryKey] ) ) === false ) {
            return $this;
        }

        foreach ( $row as $key => $val ) {
            $this->data[$key] = $val;
        }
        $this->clearDirty();
        return $this;
    }
}
