<?php

namespace Jackbooted\Cron;

use \Jackbooted\Forms\CRUD;
use \Jackbooted\Html\WebPage;
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
class CronManager extends WebPage {

    /**
     * @return string
     */
    public function index() {
        $dao = new CronDAO ();
        $cols = array_flip( $dao->objToRel( [ 'command' => 0, 'priority' => 1, 'result' => 2, 'runTime' => 3 ] ) );

        $crud = new CRUD( $dao->tableName );
        $crud->setColDisplay( $cols[0], CRUD::DISPLAY );
        $crud->setColDisplay( $cols[1], [ CRUD::SELECT, [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ] ] );
        $crud->setColDisplay( $cols[2], CRUD::DISPLAY );
        $crud->setColDisplay( $cols[3], CRUD::TIMESTAMP );
        return $crud->index();
    }

    public static function cleanup( $numDays = 5 ) {
        $keepSeconds = time() - ( $numDays * 24 * 60 * 60 );
        $deletedRecords = DB::exec( DB::DEF, 'DELETE from tblCronQueue WHERE fldRunTime<?', $keepSeconds );
        return [ 0, "Deleted: $deletedRecords" ];
    }

}
