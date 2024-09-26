<?php

namespace Jackbooted\Cron;

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
class Cron extends \Jackbooted\DB\ORM {
    const BATCH_SIZE      = 10;
    const STATUS_NEW      = 'NEW';
    const STATUS_RUNNING  = 'RUNNING';
    const STATUS_COMPLETE = 'COMPLETE';

    protected static $dao  = null;
    public static $statusList = [
        self::STATUS_NEW,
        self::STATUS_RUNNING,
        self::STATUS_COMPLETE,
    ];

    public static function init() {
        if ( self::$dao == null ) {
            self::$dao = new CronDAO ();
        }
    }

    public static function factory( $data ) {
        $clazz = __CLASS__;
        return new $clazz( $data );
    }

    private static function jobScriptExists( $checkDir ) {
        echo "Checking if {$checkDir}/job.sh exists\n";
        return file_exists( $checkDir . '/job.sh' );
    }

    private static function findJobScript( ) {
        // Look in the root directory first
        $jackDir = \Jackbooted\Config\Cfg::get( 'site_path' );
        if ( self::jobScriptExists( $jackDir ) ) return $jackDir;

        // check for the scripts folder one directory up
        $jackDirUpScripts = dirname( \Jackbooted\Config\Cfg::get( 'site_path' ) ) . '/scripts';
        if ( self::jobScriptExists( $jackDirUpScripts ) ) return $jackDirUpScripts;

        $jackDirUp = dirname( \Jackbooted\Config\Cfg::get( 'site_path' ) );
        if ( self::jobScriptExists( $jackDirUp ) ) return $jackDirUp;

        $jackDirScripts = \Jackbooted\Config\Cfg::get( 'site_path' ) . '/scripts';
        if ( self::jobScriptExists( $jackDirScripts ) ) return $jackDirScripts;

        return false;
    }

    public static function defaultRun( $cronCmd ) {
        if ( ( $jackDir = self::findJobScript() ) === false ) {
            return 'Error: cannot find job.sh';
        }

        $command = sprintf( '/usr/bin/bash -c "exec nohup setsid /usr/bin/bash %s/job.sh %s > /dev/null 2>&1 &"', $jackDir, $cronCmd );
        exec( $command );
        return 'Executed: ' . $command;
    }

    public static function run( $callbackRunner=null ) {
        if ( $callbackRunner == null ) {
            $callbackRunner = [ __CLASS__, 'defaultRun' ];
        }

        $msgList = [];
        $processed = 0;

        // This will load up the cron queue
        \Jackbooted\Cron\Scheduler::check();

        $pageTimer = new \Jackbooted\Time\Stopwatch( 'Run time for ' . __METHOD__ );
        while ( $pageTimer->getTime() < 60 ) {
            $cronJobList = self::getList( 1 );
            if ( count( $cronJobList ) <= 0 ) {
                break;
            }

            foreach ( $cronJobList as $idx => $cronJob ) {
                $msgList[] = "Job {$idx}: {$cronJob->id} {$cronJob->command} {$cronJob->message} " . \Jackbooted\Time\Stopwatch::timeToDB();
                $processed ++;

                $cronJob->status  = self::STATUS_RUNNING;
                $cronJob->runTime = \Jackbooted\Time\Stopwatch::timeToDB();
                $cronJob->result  = 0;
                $cronJob->save();

                $msgList[] = call_user_func( $callbackRunner, $cronJob->command );

                $cronJob->status  = self::STATUS_COMPLETE;
                $cronJob->save();
            }
        }
        return [ $processed, $msgList ];
    }

    public static function getList( $batchSize = self::BATCH_SIZE ) {
        $table = self::$dao->search( [ 'where' => [ 'status' => self::STATUS_NEW ], 'limit' => $batchSize, 'order' => [ 'priority' ] ] );
        return self::tableToObjectList( $table );
    }

    /**
     * Generates the html for cron iframe
     */
    public static function iFrame() {
        $cronUrl  = Cfg::get( 'site_url' ) . '/cron.php';
        $cronHtml = <<<HTML
            <iframe src="{$cronUrl}" frameboarder="1" scrolling="yes" width="620" height="100">
                <p>Your browser does not support iframes.</p>
            </iframe><br/>
HTML;
        return $cronHtml;
    }

    /**
     * @param  $data
     * @return void
     */
    public function __construct( $data ) {
        parent::__construct( self::$dao, $data );
    }

    public static function start( $job ) {
        $job->complete = 0;
        $job->save();
        return $job;
    }

    public static function end( $job ) {
        $job->complete = 100;
        $job->save();
        return $job;
    }

    public static function setStatus( $job, $percentComplete ) {
        $job->complete = $percentComplete;
        $job->save();
        return $job;
    }

    public static function add( $command, $id = 0, $priority = 0 ) {
        $data = [
            'command'  => $command,
            'ref'      => $id,
            'status'   => self::STATUS_NEW,
            'priority' => $priority
        ];
        $cronJob = new Cron( $data );
        $cronJob->save();
        return $cronJob;
    }

    public function getActive( $ref = null ) {
        $params = [ self::STATUS_COMPLETE ];
        $where = "fldStatus<>?";
        if ( $ref != null ) {
            $params[] = $ref;
            $where .= ' AND fldRef=?';
        }
        return self::$dao->getRowCount( $where, $params );
    }

    public function getNew( $ref = null ) {
        $param = [ self::STATUS_NEW ];
        $where = 'fldStatus=?';
        if ( $ref != null ) {
            $param[] = $ref;
            $where . ' AND fldRef=?';
        }
        return self::$dao->getRowCount( $where, $param );
    }
}

class CronDAO extends \Jackbooted\DB\DAO {
    public function __construct() {
        $pre      = \App\App::$dbPrefix;
        $this->db = \App\App::DB;

        $this->primaryKey     = 'fldCronQueueID';
        $this->tableName      = $pre . 'tblcronqueue';
        $this->tableStructure = <<<SQL
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
                `{$this->primaryKey}`  int(11)      NOT NULL AUTO_INCREMENT,

                fldRef          varchar(11)  DEFAULT NULL,
                fldCommand      varchar(255) DEFAULT NULL,
                fldPriority     char(3) NOT  NULL DEFAULT '9',
                fldStatus       enum('NEW','RUNNING','COMPLETE') NOT NULL DEFAULT 'NEW',
                fldRunTime      varchar(30)  DEFAULT NULL,
                fldReturnValue  char(3)      DEFAULT NULL,
                fldReturnOutput varchar(255) DEFAULT NULL,

                PRIMARY KEY ({$this->primaryKey})
            ) ENGINE=MyISAM
SQL;

        $this->orm = [
            'ref'      => 'fldRef',
            'command'  => 'fldCommand',
            'cmd'      => 'fldCommand',
            'priority' => 'fldPriority',
            'status'   => 'fldStatus',
            'runTime'  => 'fldRunTime',
            'result'   => 'fldReturnValue',
            'message'  => 'fldReturnOutput'
        ];

        parent::__construct();
    }
}

Cron::init();
CronDAO::init();
