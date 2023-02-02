<?php

namespace Jackbooted\Util;

use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBTable;

/**
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 *
 */
class XLS extends \Jackbooted\Util\JB {

    public static function output( $table, $name = '' ) {
        if ( !is_object( $table ) && !is_array( $table ) ) {
            exit;
        }
        if ( $name == '' ) {
            $name = 'output' . Invocation::next();
        }
        if ( !preg_match( '/^.*\.xls$/i', $name ) ) {
            $name .= '.xls';
        }

        $fileName = PHPExt::getTempDir() . '/' . $name;

        $workbook = new Spreadsheet_Excel_Writer( $fileName );
        $worksheet = &$workbook->addWorksheet( basename( $name ) );
        $rowIdx = 0;

        if ( $table instanceof DBTable || is_array( $table ) ) {
            foreach ( $table as $row ) {

                if ( $rowIdx == 0 ) {
                    foreach ( array_keys( $row ) as $col => $heading ) {
                        $worksheet->write( $rowIdx, $col, $heading );
                    }
                    $rowIdx ++;
                }

                foreach ( array_values( $row ) as $col => $val ) {
                    $worksheet->write( $rowIdx, $col, $val );
                }
                $rowIdx ++;
            }
        }
        else if ( $table instanceof PDOStatement ) {
            while ( $row = $table->fetch( DB::FETCH_ASSOC ) ) {

                if ( $rowIdx == 0 ) {
                    foreach ( array_keys( $row ) as $col => $heading ) {
                        $worksheet->write( $rowIdx, $col, $heading );
                    }
                    $rowIdx ++;
                }

                foreach ( array_values( $row ) as $col => $val ) {
                    $worksheet->write( $rowIdx, $col, $val );
                }
                $rowIdx ++;
            }
        }

        $workbook->close();
        $workbook->send( $name );
        $fp = fopen( $fileName, 'rb' );
        fpassthru( $fp );
        fclose( $fp );
        unlink( $fileName );
        exit;
    }

}
