<?php

namespace Jackbooted\Cron;

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
class Scheduler extends \Jackbooted\DB\ORM {

    const ACTIVE = 'Yes';

    private static $dao = null;
    private static $debug = FALSE;

    /**
     * @return void
     */
    public static function init() {
        if ( self::$dao == null ) {
            $clazz = static::class . 'DAO';
            self::$dao = new $clazz();
        }
        if ( isset( $_SERVER['argv'] ) && is_array( $_SERVER['argv'] ) ) {
            self::$debug        = in_array( '-v',   $_SERVER['argv'] ) ||
                                  in_array( '-vv',  $_SERVER['argv'] );
        }
    }

    public static function getRowCount() {
        return self::$dao->getRowCount();
    }

    public static function findCommand( $command ) {
        $search = [ 'where' => [ 'command' => $command ] ];
        $table = self::$dao->search( $search );
        return self::tableToObjectList( $table );
    }

    public static function displayList( $order='', $limits='' ) {
        $tName = self::$dao->tableName;
        $sql= <<<SQL
            SELECT *
            FROM   {$tName}
            {$order}
            {$limits}
        SQL;

        $tab = \Jackbooted\DB\DBTable::factory( self::$dao->db, $sql, null, \Jackbooted\DB\DB::FETCH_ASSOC );
        return self::tableToObjectList( $tab );
    }

    public static function jobs() {
        $jobList = [];
        foreach( self::getList() as $cronEntry ) {
            $jobList[] = $cronEntry->getData();
        }
        return $jobList;
    }

    public static function getList( $all = false ) {
        if ( $all ) {
            $search = [ 'where' => [], 'order' => [ 'group' => 'ASC', 'cmd' => 'ASC' ] ];
        }
        else {
            $search = [ 'where' => [ 'active' => self::ACTIVE ], 'order' => [ 'group' => 'ASC', 'cmd' => 'ASC' ] ];
        }

        $table = self::$dao->search( $search );
        return self::tableToObjectList( $table );
    }

    private static function echoD ( $msg ) {
        if ( self::$debug ) {
            echo $msg;
        }
    }

    /**
     * @param  $data
     * @return void
     */
    public function __construct( $data ) {
        parent::__construct( self::$dao, $data );
    }

    /**
     * Check if there are any upcoming schedules
     */
    public static function check() {
        $numAdded = 0;
        $now = time();

        foreach ( self::getList() as $sheduleItem ) {

            if ( $sheduleItem->lastRun == '' ) {
                $sheduleItem->lastRun = date( 'Y-m-d H:i', CronParser::lastRun( $sheduleItem->cron ) );
                $sheduleItem->save();
            }

            $storedLastRunTime = strtotime( $sheduleItem->lastRun );
            $lastRun = CronParser::lastRun( $sheduleItem->cron );

            // This looks at when the item had run. If the stored value is less than
            // the calculated value means that we have past a run period. So need to run
            if ( $storedLastRunTime < $lastRun ) {

                // Update the run time to now
                $sheduleItem->lastRun = date( 'Y-m-d H:i', $lastRun );
                $sheduleItem->save();

                $diffSec = abs( $lastRun - $now );
                if ( $diffSec < 60 ) {
                    $data = [
                        'ref'     => $sheduleItem->id,
                        'cmd'     => $sheduleItem->cmd,
                        'message' => $sheduleItem->cron,
                    ];
                    $job = new Cron( $data );
                    $job->save();
                    $numAdded ++;
                }
            }
        }
        return $numAdded;
    }

}
class SchedulerDAO extends \Jackbooted\DB\DAO {
    public function __construct() {
        $pre      = \App\App::$dbPrefix;
        $this->db = \App\App::DB;

        $this->primaryKey     = 'fldSchedulerID';
        $this->tableName      = $pre . 'tblscheduler';

        $this->tableStructure = <<<SQL
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
                `{$this->primaryKey}`  int(11)      NOT NULL AUTO_INCREMENT,

                fldGroup       varchar(30)     DEFAULT NULL,
                fldCommand     varchar(255)     DEFAULT NULL,
                fldDescription varchar(255)     DEFAULT NULL,
                fldActive      enum('Yes','No') NOT NULL DEFAULT 'Yes',
                fldStartTime   varchar(40)      DEFAULT NULL,
                fldCron        varchar(100)     DEFAULT NULL,
                fldLastRun     varchar(40)      DEFAULT NULL,

                PRIMARY KEY ({$this->primaryKey})
            ) ENGINE=MyISAM
        SQL;

        $this->orm = [
            'group'   => 'fldGroup',
            'command' => 'fldCommand',
            'cmd'     => 'fldCommand',
            'desc'    => 'fldDescription',
            'active'  => 'fldActive',
            'start'   => 'fldStartTime',
            'cron'    => 'fldCron',
            'lastRun' => 'fldLastRun'
        ];

        parent::__construct();
    }

}
SchedulerDAO::init();
Scheduler::init();