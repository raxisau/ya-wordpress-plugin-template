<?php

namespace Jackbooted\DB;

use \Jackbooted\Config\Cfg;
use \Jackbooted\Util\Log4PHP;
use \Jackbooted\DB\DB;

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
class Migrations extends \Jackbooted\Html\WebPage {

    public static function init() {
        self::$log = Log4PHP::logFactory( __CLASS__ );
    }

    public static function migrate() {
        $maxRun = 0;
        $runItems = [];
        foreach ( DBTable::factory( DB::DEF, 'SELECT * FROM tblMigration' ) as $row ) {
            if ( (int) $row['fldRun'] > $maxRun ) {
                $maxRun = (int) $row['fldRun'];
            }

            if ( !isset( $runItems[$row['fldClass']] ) ) {
                $runItems[$row['fldClass']] = [];
            }

            $runItems[$row['fldClass']][] = $row['fldMethod'];
        }

        $maxRun += 1;
        $html = '';

        // Go through all the migration classes
        foreach ( Cfg::get( 'migration', [] ) as $migrationClass ) {
            $clazz = new \ReflectionClass( $migrationClass );

            // If new class then just add empty list
            if ( !isset( $runItems[$migrationClass] ) ) {
                $runItems[$migrationClass] = [];
            }

            // get a list of methods to run
            $methodList = [];
            foreach ( $clazz->getMethods() as $method ) {
                if ( in_array( $method->name, $runItems[$migrationClass] ) ) {
                    continue;
                }
                if ( strpos( $method->name, 'migrate' ) !== 0 ) {
                    continue;
                }

                // Add the name to the list
                $methodList[] = $method->name;
            }

            // Sort so that it will be date ordered
            sort( $methodList );

            foreach ( $methodList as $method ) {
                if ( ( $result = call_user_func( [ $migrationClass, $method ] ) ) === false ) {
                    $html .= "There is a problem running {$migrationClass}::{$method}<br/>\n";
                }
                else {
                    $html .= $result;
                    DB::exec( DB::DEF, 'INSERT INTO tblMigration (fldMigrationID,fldRun,fldClass,fldMethod) VALUES (?,?,?,?)', [ DBMaintenance::dbNextNumber( DB::DEF, 'tblMigration' ),
                        $maxRun,
                        $migrationClass,
                        $method ] );
                }
            }
        }
        return $html;
    }

}
