<?php
namespace App\Models;

class TblCurrencies extends \Jackbooted\DB\ORM {
    const CURRENCY_USD = 1;
    const CURRENCY_AUD = 2;
    const CURRENCY_NZD = 4;
    const CURRENCY_EUR = 5;
    const CURRENCY_CAD = 6;

    const PRECISION_0  = 0;
    const PRECISION_1  = 1;
    const PRECISION_2  = 2;

    private static $dao = null;
    public  static $desiredCurrency  = null;
    public  static $defaultCurrency;
    public  static $currencyTableIDX  = null;
    public  static $currencyTableCode = null;

    public static function init () {
        if ( self::$dao == null ) {
            $daoClass = __CLASS__ . 'DAO';
            self::$dao = new $daoClass();
        }
    }

    public static function update() {
        $culrOpts = [
            CURLOPT_URL            => 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        try {
            $ch = curl_init( $culrOpts[CURLOPT_URL] );
            curl_setopt_array( $ch, $culrOpts );
            if ( ( $rawFeed = curl_exec( $ch ) ) === false ) {
                $rawFeed = '';
            }
        }
        finally {
            curl_close( $ch );
        }
        $rawFeed = explode( "\n", $rawFeed );
        $exchangeRates = [];
        $exchangeRates["EUR"] = 1;
        $regEx = '/currency=' . "'" . '([^' . "'" . ']+)' . "'" . '\s*rate=' . "'" . '([^' . "'" . ']+)/';
        foreach ( $rawFeed as $line ) {
            if ( preg_match( $regEx, $line, $matches ) ) {
                $exchangeRates[$matches[1]] = $matches[2];
            }
        }

        foreach ( self::$dao->search( [ 'where' => '1=1' ] ) as $row ) {
            $currency = self::factory( $row );
            $currency->rate = ( $currency->code == 'USD' ) ? '1.00000' : (float)$exchangeRates['EUR'] / (float)$exchangeRates['USD'] * (float)$exchangeRates[$currency->code];
            $currency->save();
        }
    }

    public static function convert( $amountList, $fromID, $toID, $overrideFormat=-1 ) {
        self::loadCurrencies();

        $from = self::$currencyTableIDX[$fromID];
        $to   = self::$currencyTableIDX[$toID];
        $format = ( $overrideFormat >= 0 ) ? $overrideFormat : $to->format;

        $convertList = [];
        foreach( $amountList as $amount ) {
            $convertList[] = round( ( $from->id == $to->id ) ? $amount : $amount / $from->rate * $to->rate, $format );
        }

        return $convertList;
    }

    public static function setDesiredCurrency( $cid=false ) {
        self::$desiredCurrency = ( $cid === false ) ? self::CURRENCY_USD : $cid;
    }

    public static function loadCurrencies() {
        if ( self::$desiredCurrency == null ) {
            self::setDesiredCurrency();
        }

        self::$currencyTableIDX  = [];
        self::$currencyTableCode = [];
        foreach ( self::$dao->search( [ 'where' => '1=1' ] ) as $row ) {
            $currency = self::factory( $row );
            self::$currencyTableIDX[$currency->id]    = $currency;
            self::$currencyTableCode[$currency->code] = $currency;
            if ( $currency->default == 1 ) {
                self::$defaultCurrency = $currency;
            }
        }
    }

    public static function getConversion() {
        if ( self::$currencyTableIDX == null ) {
            self::loadCurrencies();
        }
        return [ self::$defaultCurrency, self::$currencyTableIDX[self::$desiredCurrency] ];
    }

    /*
     * Converts the passed system value into local value and displays the currency
     */
    public static function toDisplay( $amount, $overrideFormat=-1 ) {
        if ( ! is_numeric( $amount ) ) {
            $amount = 0;
        }

        [ $from, $to ] = self::getConversion();
        $format = ( $overrideFormat >= 0 ) ? $overrideFormat : $to->format;
        $convAmount = round( ( $from->id == $to->id ) ? $amount : $amount / $from->rate * $to->rate, $format );

        return $to->prefix . number_format( $convAmount,  $format ) . $to->suffix;
    }

    /*
     * Converts the passed system value into local value
     */
    public static function toValue( $amount, $precision=-1 ) {
        if ( $precision == -1 ) {
            $precision = 2;
        }

        [ $from, $to ] = self::getConversion();
        $convAmount = ( $from->id == $to->id ) ? $amount : $amount / $from->rate * $to->rate;

        return round( $convAmount, $precision );
    }

    /*
     * Takes the user value and converts it to a system value.
     * In this case the system currency is USD so it is converted into
     */
    public static function fromDisplay( $amount, $precision=-1 ) {
        if ( $precision == -1 ) {
            $precision = 2;
        }

        [ $from, $to ] = self::getConversion();
        $convAmount = ( $from->id == $to->id ) ? $amount : $amount / $to->rate * $from->rate;

        return round( $convAmount, $precision );
    }

    public static function loadCode ( $code ) {
        if ( self::$currencyTableIDX == null ) {
            self::loadCurrencies();
        }

        if ( isset( self::$currencyTableCode[$code] ) ) {
            return self::$currencyTableCode[$code];
        }

        return false;
    }

    public static function initialize() {
        $tName = self::$dao->tableName;

        \Jackbooted\DB\DB::exec( self::$dao->db, "TRUNCATE TABLE {$tName}" );

        $defaultValues = [
            [ 'id' => 1, 'code' => 'USD', 'prefix' => '$', 'suffix' => ' USD', 'format' => 2, 'rate' => '1', 'default' => 1, ],
            [ 'id' => 2, 'code' => 'AUD', 'prefix' => '$', 'suffix' => ' AUD', 'format' => 2, 'rate' => '1', 'default' => 0, ],
            [ 'id' => 4, 'code' => 'NZD', 'prefix' => '$', 'suffix' => ' NZD', 'format' => 2, 'rate' => '1', 'default' => 0, ],
            [ 'id' => 5, 'code' => 'EUR', 'prefix' => '€', 'suffix' => ' EUR', 'format' => 2, 'rate' => '1', 'default' => 0, ],
            [ 'id' => 6, 'code' => 'CAD', 'prefix' => '$', 'suffix' => ' NZD', 'format' => 2, 'rate' => '1', 'default' => 0, ],
        ];

        // Sorry about the formatting, I just wanted to be very explicit about the names and formatting
        $sqlInsert = "INSERT INTO {$tName} (                             `id`,       `code`,       `prefix`,       `suffix`,       `format`,       `rate`,        `default`) VALUES (?,?,?,?,?,?,?)";
        foreach ( $defaultValues as $def ) {
             \Jackbooted\DB\DB::exec( self::$dao->db, $sqlInsert, [ $def['id'], $def['code'], $def['prefix'], $def['suffix'], $def['format'], $def['rate'],  $def['default'] ] );
        }
    }

    public function __construct( $data ) {
        parent::__construct( self::$dao, $data );
    }

    public function codeLower() {
        return strtolower( $this->code );
    }
}

class TblCurrenciesDAO extends \Jackbooted\DB\DAO  {
    public function __construct () {
        $pre      = \App\App::$dbPrefix;
        $this->db = \App\App::DB;

        $this->primaryKey     = 'id';
        $this->tableName      = $pre . 'tblcurrencies';
        $this->tableStructure = <<<SQL
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
                {$this->primaryKey} int(11) unsigned NOT NULL AUTO_INCREMENT,
                `code` text NOT NULL,
                `prefix` text NOT NULL,
                `suffix` text NOT NULL,
                `format` int(1) NOT NULL,
                `rate` decimal(10,5) NOT NULL DEFAULT 1.00000,
                `default` int(1) NOT NULL,
                PRIMARY KEY (`{$this->primaryKey}`)
            ) ENGINE=MyISAM
        SQL;

        parent::__construct();
    }
}
TblCurrenciesDAO::init();
TblCurrencies::init();
