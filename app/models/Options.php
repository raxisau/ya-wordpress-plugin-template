<?php
namespace App\Models;

class Options extends \Jackbooted\DB\ORM {
    const STATE_TRUE  = 'TRUE';
    const STATE_FALSE = 'FALSE';
    const STATE_YES   = 'YES';
    const STATE_NO    = 'NO';

    const STATE_NEW        = 'NEW';
    const STATE_PROCESSING = 'Processing';
    const STATE_COMPLETE   = 'Complete';

    private static $dao = null;

    public static function init () {
        if ( self::$dao == null ) {
            $daoClass = __CLASS__ . 'DAO';
            self::$dao = new $daoClass();
        }
    }

    public static function findKey( $likeKey ) {
        $tName = self::$dao->tableName;
        $sql = <<<SQL
            SELECT fldLabel
            FROM {$tName}
            WHERE fldLabel LIKE ?
        SQL;
        return \Jackbooted\DB\DB::oneColumn( self::$dao->db, $sql, [ $likeKey ] );
    }

    public static function find( $likeKey ) {
        $tName      = self::$dao->tableName;
        $nocache    = rand();
        $comparison = ( strpos( $likeKey, '%' ) === false ) ? '=' : 'LIKE';

        $sql = <<<SQL
            SELECT *
            FROM   {$tName}
            WHERE  fldLabel {$comparison} ?
            AND    {$nocache}={$nocache}
        SQL;
        return \Jackbooted\DB\DBTable::factory( self::$dao->db, $sql, [ $likeKey ], \Jackbooted\DB\DB::FETCH_ASSOC );
    }

    public static function get( $key, $def='' ) {
        $tab = self::find( $key );

        if ( $tab->getRowCount() > 0 ) {
            return self::factory( $tab->getRow() );
        }

        $obj = self::factory([ 'fldLabel' => $key, 'fldValue' => $def ]);
        $obj->save();

        return $obj;
    }

    public static function put ( $key, $value ) {
        $obj = self::get( $key, $value );
        return $obj->update( $value );
    }

    public function __construct( $data ) {
        parent::__construct ( self::$dao, $data );
    }

    public function update( $value ) {
        if ( $this->fldValue != $value ) {
            $this->fldValue = $value;
            $this->save();
        }
        return $this;
    }
}
class OptionsDAO extends \Jackbooted\DB\DAO  {
    public function __construct () {
        $pre      = \App\App::$dbPrefix;
        $this->db = \App\App::DB;

        $this->primaryKey     = 'fldOptionsID';
        $this->tableName      = $pre . 'tbloptions';

        $this->tableStructure = <<<SQL
            CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
                `{$this->primaryKey}` int(11) NOT NULL AUTO_INCREMENT,

                `fldLabel`       varchar(200),
                `fldValue`       text,
                `fldEditable`    char(3) DEFAULT 'NO',
                `fldDescription` varchar(200) DEFAULT NULL,

                PRIMARY KEY `{$this->primaryKey}` (`{$this->primaryKey}`),
                UNIQUE KEY `fldLabel` (`fldLabel`)
             ) ENGINE=MyISAM
SQL;
        $this->orm = [
            'key'         => 'fldLabel',
            'label'       => 'fldLabel',
            'value'       => 'fldValue',
            'editable'    => 'fldEditable',
            'description' => 'fldDescription',
        ];
        parent::__construct();
    }
}
OptionsDAO::init();
Options::init();
