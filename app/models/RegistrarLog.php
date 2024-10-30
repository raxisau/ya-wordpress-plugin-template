<?php
namespace App\Models;

use \Jackbooted\Time\Stopwatch;

class RegistrarLog extends \Jackbooted\DB\ORM {
    private static $dao = null;

    public static function init() {
        if ( self::$dao == null ) {
            $daoClass = __CLASS__ . 'DAO';
            self::$dao = new $daoClass();
        }
    }

    public static function start( $command, $params=[] ) {

        if ( is_array( $params ) ) {
            foreach ( [ 'password', 'eppCode' ] as $idx ) {
                if ( isset( $params[$idx] ) ) {
                    $params[$idx] = 'redacted';
                }
            }
        }

        $regLog = new RegistrarLog( );
        $regLog->command = $command;
        $regLog->params  = self::stringify( $params );
        $regLog->start   = Stopwatch::timeToDB();
        return $regLog;
    }

    public function end( $result ) {
        $this->result = self::stringify( $result );
        $this->end    = Stopwatch::timeToDB();
        $this->save();
        return $result;
    }

    private static function stringify( $value ) {
        if ( is_object( $value ) ) {
             return json_encode( $value->getResult(), JSON_PRETTY_PRINT );
        }

        if ( is_string( $value ) ) {
            return $value;
        }

        return json_encode( $value, JSON_PRETTY_PRINT );
    }

    public function __construct( $data=[] ) {
        parent::__construct( self::$dao, $data );
    }
}

class RegistrarLogDAO extends \Jackbooted\DB\DAO {
    public function __construct() {

        $pre      = \App\App::$dbPrefix;
        $this->db = \App\App::DB;

        $this->primaryKey     = 'fldRegistrarLogID';
        $this->tableName      = $pre . 'tblregistrarlog';
        $this->tableStructure = <<<SQL
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
                {$this->primaryKey} int(11) unsigned NOT NULL AUTO_INCREMENT,

                `fldCommand` varchar(200) DEFAULT NULL,
                `fldParameters` text DEFAULT NULL,
                `fldStart` datetime DEFAULT NULL,
                `fldEnd` datetime DEFAULT NULL,
                `fldResult` text DEFAULT NULL,

                PRIMARY KEY (`{$this->primaryKey}`)
            ) ENGINE=MyISAM
        SQL;

        $this->orm = [
            'command' => 'fldCommand',
            'params'  => 'fldParameters',
            'start'   => 'fldStart',
            'end'     => 'fldEnd',
            'result'  => 'fldResult',
        ];

        parent::__construct();
    }
}
RegistrarLogDAO::init();
RegistrarLog::init();
