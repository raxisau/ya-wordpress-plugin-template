<?php
namespace App\Models;

class JobLog extends \Jackbooted\DB\ORM {
    private static $dao = null;

    public static function init () {
        if ( self::$dao == null ) {
            $daoClass = __CLASS__ . 'DAO';
            self::$dao = new $daoClass();
        }
    }

    public function __construct( $data ) {
        parent::__construct ( self::$dao, $data );
    }
}
class JobLogDAO extends \Jackbooted\DB\DAO  {
    public function __construct () {
        $pre      = \App\App::$dbPrefix;
        $this->db = \App\App::DB;

        $this->primaryKey     = 'fldJobLogID';
        $this->tableName      = $pre . 'tbljoblog';

        $this->tableStructure = <<<SQL
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
              {$this->primaryKey} int(11) unsigned NOT NULL AUTO_INCREMENT,
              `fldStartTime` datetime DEFAULT NULL,
              `fldEndTime` datetime DEFAULT NULL,
              `fldJobName` varchar(100) DEFAULT NULL,
              `fldExitCode` varchar(3) DEFAULT NULL,
              `fldOutputLen` int(11) DEFAULT NULL,
              `fldJobOutput` text,
              PRIMARY KEY (`{$this->primaryKey}`)
            ) ENGINE=MyISAM
        SQL;

        $this->orm = [
            'startTime' => 'fldStartTime',
            'endTime'   => 'fldEndTime',
            'jobName'   => 'fldJobName',
            'exitCode'  => 'fldExitCode',
            'outputLen' => 'fldOutputLen',
            'jobOutput' => 'fldJobOutput',

            'start_time' => 'fldStartTime',
            'end_time'   => 'fldEndTime',
            'job_name'   => 'fldJobName',
            'exit_code'  => 'fldExitCode',
            'output_len' => 'fldOutputLen',
            'job_output' => 'fldJobOutput',
        ];
        parent::__construct();
    }
}
JobLogDAO::init();
JobLog::init();
