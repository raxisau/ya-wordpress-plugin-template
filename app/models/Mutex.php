<?php
namespace App\Models;

use \Jackbooted\DB\DB;

class Mutex extends \Jackbooted\DB\ORM {
    private static $dao = null;

    public static function init () {
        if ( self::$dao == null ) {
            $daoClass = __CLASS__ . 'DAO';
            self::$dao = new $daoClass();
        }
    }

    public static function load( $mutexName ) {
        $where = [ 'mutexName' => $mutexName ];
        foreach ( self::$dao->search(  [ 'where' => $where ] ) as $row ) {
            return self::factory( $row );
        }
        return false;
    }

    public static function lock( $mutexName, $timeout=10 ) {
        $uuid = '' . microtime();
        $tName = self::$dao->tableName;
        $sql= "INSERT IGNORE INTO {$tName} (fldMutexName,fldLockName) VALUES(?,?)";
        $counter = 0;
        while ( DB::exec( self::$dao->db, $sql, [ $mutexName, $uuid ] ) != 1 ) {
            if ( $counter > $timeout ) {
                $sql= "DELETE FROM {$tName} WHERE fldMutexName=?";
                DB::exec( self::$dao->db, $sql, [ $mutexName ] );
                return false;
            }
            sleep( 1 );
            $uuid = '' . microtime();
            $counter ++;
        }
        return self::load( $mutexName );
    }

    public function __construct( $data ) {
        parent::__construct ( self::$dao, $data );
    }

    public function unlock() {
        $this->delete();
    }
}
class MutexDAO extends \Jackbooted\DB\DAO  {
    public function __construct () {
        $pre      = \App\App::$dbPrefix;
        $this->db = \App\App::DB;

        $this->primaryKey     = 'fldMutexID';
        $this->tableName      = $pre . 'tblmutex';
        $this->tableStructure = <<<SQL
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
              `{$this->primaryKey}` int(11) NOT NULL AUTO_INCREMENT,
              `fldMutexName`  varchar(100)  NOT NULL,
              `fldLockName`   varchar(100)  DEFAULT NULL,
              PRIMARY KEY (`{$this->primaryKey}`),
              UNIQUE  KEY `fldMutexName` (`fldMutexName`)
            ) ENGINE=MyISAM
SQL;

        $this->orm = [
            'mutexName' => 'fldMutexName',
            'lockName'  => 'fldLockName',
        ];

        parent::__construct();
    }
}
MutexDAO::init();
Mutex::init();
