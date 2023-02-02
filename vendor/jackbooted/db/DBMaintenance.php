<?php

namespace Jackbooted\DB;

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
class DBMaintenance extends \Jackbooted\Util\JB {

    public static function dbNextNumber( $dbh, $tName ) {
        // Loop max of 10 times to get the correc next number (optimistic locking)
        for ( $i = 0; $i < 10; $i++ ) {
            $qry = 'SELECT fldNext,fldFormat FROM tblNextNumber WHERE fldTable=?';
            $row = DB::oneRow( $dbh, $qry, $tName, DB::FETCH_ASSOC );
            if ( $row === false ) {
                self::addTableToNextNumber( $tName, 'XX000000', 'Auto Inserted by dbNextNumber' );
                $row = DB::oneRow( $dbh, $qry, $tName, DB::FETCH_ASSOC );
                if ( $row === false ) {
                    return false;
                }
            }

            $nextNumberS = $row['fldNext'];
            $nextNumber = intval( $nextNumberS );
            $format = $row['fldFormat'];

            $updatedNumber = $nextNumber + 1;
            $qry = 'UPDATE tblNextNumber SET fldNext=? WHERE fldTable=? AND fldNext=?';
            $rowsAffected = DB::exec( $dbh, $qry, [ $updatedNumber, $tName, $nextNumberS ] );
            if ( $rowsAffected == 1 ) {
                break;
            }
        }

        // Apply the format to the number. This will be stored in the database
        return substr( $format, 0, strlen( $format ) - strlen( $nextNumberS ) ) . $nextNumberS;
    }

    public static function getTableList() {
        $qry = ( DB::driver() == DB::SQLITE ) ? "SELECT name FROM sqlite_master WHERE type='table'" : 'SHOW TABLES';
        return DBTable::factory( DB::DEF, $qry, null, DB::FETCH_NUM )->getColumn( 0 );
    }

    public static function addTableToNextNumber( $tName, $fmt, $comment = "" ) {
        // See if the table has been created
        $tableExists = DB::oneValue( DB::DEF, 'SELECT count(fldNextNumberID) FROM tblNextNumber WHERE fldTable=?', $tName );
        if ( $tableExists != 0 ) {
            return false;
        }

        $key = self::dbNextNumber( DB::DEF, 'tblNextNumber' );
        $lines = DB::exec( DB::DEF, "INSERT INTO tblNextNumber VALUES (?,?,1,?,?,? )", [ $key, $tName, $fmt, $comment, self::getTableChecksum( $tName ) ] );
        return ( $lines > 0 );
    }

    public static function updateTableChecksum( $tableName ) {
        $checksum = self::getTableChecksum( $tableName );
        DB::exec( DB::DEF, 'UPDATE tblNextNumber SET fldTableChecksum=? WHERE fldTable=?', [ $checksum, $tableName ] );
    }

    public static function getTableChecksum( $tableName ) {
        $createSyntax = self::getTableSyntax( $tableName );
        return md5( $createSyntax );
    }

    public static function getTableComments( $tableName ) {
        $attributes = [];
        $createSyntax = self::getTableSyntax( $tableName );
        $p = strpos( $createSyntax, "COMMENT=" );
        if ( $p ) {
            $comments = substr( $createSyntax, $p + 9 );
            $comments = substr( $comments, 0, strlen( $comments ) - 1 );
            foreach ( explode( ';', $comments ) as $attrib ) {
                list ( $key, $val ) = explode( '=', $attrib );
                $attributes[$key] = $val;
            }
        }
        return $attributes;
    }

    public static function addTableComments( $tableName, $attributes ) {
        $allComments = array_merge( self::getTableComments( $tableName ), $attributes );
        self::setTableComments( $allComments );
    }

    public static function setTableComments( $tableName, $attributes ) {
        $tempArray = [];
        foreach ( $attributes as $key => $val ) {
            $tempArray[] = $key . '=' . $val;
        }
        $comments = join( ';', $tempArray );
        $sql = "ALTER TABLE {$tableName} COMMENT ?";
        DB::exec( DB::DEF, $sql, $comments );
    }

    private static function getTableSyntax( $tableName ) {
        if ( DB::driver() == DB::SQLITE ) {
            return DB::oneValue( DB::DEF, "SELECT sql FROM sqlite_master where type='table' and tbl_name='$tableName'" );
        }
        else {
            $createTable = new DBTable( DB::DEF, 'SHOW CREATE TABLE ' . $tableName );
            return $createTable->getValue( 1, 0 );
        }
    }

}
