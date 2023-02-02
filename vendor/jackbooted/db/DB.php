<?php

namespace Jackbooted\DB;

use \Jackbooted\Config\Cfg;
use \Jackbooted\Util\Log4PHP;
use \Jackbooted\Util\PHPExt;

/**
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */

/**
 * DB - Database abstraction for PDO.
 * The DB Strings are created from the configuration
 * @see addDB
 */
class DB extends \Jackbooted\Util\JB {

    /**
     * Default DB is Local
     */
    const DEF = 'local';

    /**
     * Wrap up the PDO Constants.
     */
    const FETCH_ASSOC = \PDO::FETCH_ASSOC;

    /**
     * Wrap up the PDO Constants.
     */
    const FETCH_NUM = \PDO::FETCH_NUM;

    /**
     * Wrap up the PDO Constants.
     */
    const FETCH_BOTH = \PDO::FETCH_BOTH;

    /**
     * Default SQL Table Engine type.
     */
    const SQL_ENGINE = 'InnoDB';

    /**
     * Default SQL Table Charset.
     */
    const SQL_CHARSET = 'utf8';
    const SQLITE = 'sqlite';
    const MYSQL = 'mysql';
    const SQLSERVER = 'dblib';
    const ORACLE = 'oracle';

    // Keep a cache of the connections
    private static $connections = [];
    // Thelast accessed database
    private static $lastDB = null;
    // Keep a log of the number of calls
    private static $callNumber = 0;
    // Logging
    private static $log;
    private static $queryLoggingFunction;
    private static $queryLoggingLevel;
    private static $queryLogFlag;
    private static $errorLoggingFunction;
    private static $errorLoggingLevel;

    /**
     * Controls whether to use direct queries or prepared statements
     */
    private static $directQuery = true;

    /**
     * Set up the statics, sets up the logging level in one place.
     *
     * If you want to change this throughout this class, you can adjust here.
     * @since 1.0
     * @return void
     */
    public static function init() {
        self::$log = Log4PHP::logFactory( __CLASS__ );
        self::$log->setClassErrorLevel( Log4PHP::INFO );

        // Sets up the logging level in one place
        // If you want to change this throughout this class, you can adjust here
        self::$queryLoggingFunction = [ self::$log, 'info' ];
        self::$queryLoggingLevel = Log4PHP::INFO;
        self::$queryLogFlag = self::$log->isDisplayed( self::$queryLoggingLevel );

        self::$errorLoggingFunction = [ self::$log, 'error' ];
        self::$errorLoggingLevel = Log4PHP::ERROR;
    }

    /**
     * Returns the log object so that you can selectively turn on log flags.
     *
     * Eg:
     * <pre>
     *  $dbLogger = DB::getLogger ();
     *  $dbLogger->setClassOutputDevice ( Log4PHP::SCREEN );
     *  $dbLogger->setClassErrorLevel ( Log4PHP::ALL );
     *  DB::init();
     * </pre>
     * @since 1.0
     * @return Log4PHP - returns the log object.
     */
    public static function getLogger() {
        return self::$log;
    }

    /**
     * Allows a database to be created and saved in the $config.
     *
     * This allows compatibility with fab_query
     *
     * @param string $name     Name of the database connection eg: 'reg'.
     * @param string $host     Host name of the database server.
     * @param string $user     Username to access the server.
     * @param string $password Password for this account.
     * @param string $database Database name.
     * @param string $driver   Database driver - Supported drivers are 'mysql' or 'pgsql'.
     *
     * @since 1.0
     * @return void
     */
    public static function addDB( $name, $host, $user, $password, $database, $driver = 'mysql' ) {
        Cfg::set( $name . '-host', $host );
        Cfg::set( $name . '-db', $database );
        Cfg::set( $name . '-user', $user );
        Cfg::set( $name . '-pass', $password );
        Cfg::set( $name . '-driver', $driver );
    }

    /**
     * This is a standard normalizing function that can be called throughout the system.
     *
     * And will always return the PDO object that is necessary for the calls.
     * <ul>
     * <li>If the $db variable is a string it is assumed to be the index of the db connection
     * that is needed. If the connection exists already it is returned, otherwise it sets
     * up the connection using the $config variables</li>
     * <li>If $db is an object then it is assumed to be a PDO object and passed straight thru</li>
     * <li>If $db is an array then it must contain the items array ( 'hostname' => $host,
     * 'username' => $user, 'password' => $password, 'dbname'  => $database, 'driver' => 'mysql' ) If they don't exist
     * in the $config, then they are set up</li>
     * </ul>
     * @param mixed $db - This is either null, and array of the the database connection
     *                    information or the connection handle itself.
     * @since 1.0
     * @return PDO Object connection to the database.
     */
    private static function connectionFactory( $db = null, $key = null ) {

        if ( is_string( $db ) ) {
            // If this is a string then a key has been passed.
            // The key may have been set up as PDO object or
            // it might be a key from legacy config
            return self::connectionFactoryFromString( $db );
        }
        else if ( is_object( $db ) ) {
            // If this is an objecct then it is likely a PDO object
            self::$lastDB = $db;
            return self::$lastDB;
        }
        else if ( is_array( $db ) ) {
            // If this is an array then it might be a database information
            return self::connectionFactoryFromArray( $db, $key );
        }
        else {
            return self::$lastDB;
        }
    }

    private static function connectionFactoryFromString( $db ) {
        if ( isset( self::$connections[$db] ) ) {
            self::$lastDB = self::$connections[$db];
            return self::$lastDB;
        }
        else {
            $dbConnection = [
                'hostname' => Cfg::get( $db . '-host' ),
                'dbname'   => Cfg::get( $db . '-db' ),
                'username' => Cfg::get( $db . '-user' ),
                'password' => Cfg::get( $db . '-pass' ),
                'driver'   => Cfg::get( $db . '-driver', DB::MYSQL ),
            ];

            if ( $dbConnection['hostname'] != '' ) {
                return self::connectionFactoryFromArray( $dbConnection, $db );
            }
            else {
                self::logErrorMessage( 'Unknown DB: ' . $db );
                return false;
            }
        }
    }

    private static function connectionFactoryFromArray( $db, $key = null ) {
        if ( !isset( $db['driver'] ) ) {
            $db['driver'] = self::MYSQL;
        }

        if ( preg_match( '/^java:comp\/env\/jdbc\/.*$/', $db['dbname'] ) ) {
            $connectionString = $db['dbname'];
        }
        else if ( $db['driver'] == self::SQLITE ) {
            $connectionString = $db['driver'] . ':' . $db['hostname'];
        }
        else {
            $connectionString = $db['driver'] . ':host=' . $db['hostname'] . ';dbname=' . $db['dbname'];
        }

        if ( $key == null ) {
            $key = hash( 'md4', $connectionString . $db['username'] . $db['password'] );
        }

        if ( !isset( self::$connections[$key] ) ) {
            self::dbg( 'Setting up new DB conn: ' . $connectionString . ' - ' . $db['username'] );
            try {
                if ( $db['driver'] == self::SQLITE ) {
                    self::$connections[$key] = new \PDO( $connectionString );
                }
                else {
                    self::$connections[$key] = new \PDO( $connectionString, $db['username'], $db['password'] );
                }
            }
            catch ( Exception $ex ) {
                self::logErrorMessage( 'Error Setting up new DB conn: ' . $connectionString . ' - ' .
                        $db['username'] . ' - ' . $ex->getMessage() );
                return false;
            }
        }

        self::$lastDB = self::$connections[$key];
        return self::$lastDB;
    }

    /**
     * Converts the passed array to the necessary for prepared statement.
     *
     * If the array is associative then
     * it creates a :x,:y style string. If the array is numeric then creates a string with ? eg:
     * <pre>
     * $arr = array ( 'domain' => 0, 'length' => 1, 'tld' => 5, 'pricescore' => 2, 'intprice' => 4 );
     * $params = array ( 'foo' => 'dummy1', 'bar' => 'dummy2' );
     * echo 'INSERT INTO domain_info VALUES(' . DB::in ( $arr, $params ) . ')<br>';
     * echo '<pre>'; print_r ( $params ); echo '</pre>';
     *
     * $arr = array_values ( $arr );
     * $params = array ( 'dummy1', 'dummy2' );
     * echo 'INSERT INTO domain_info VALUES(' . DB::in ( $arr, $params ) . ')<br>';
     * echo '<pre>'; print_r ( $params ); echo '</pre>';
     * </pre>
     * @param mixed $values  Values that will be in the IN.
     * @param mixed &$params Adds to the parameters.
     *
     * @since 1.0
     * @return string
     */
    public static function in( $values, &$params = null ) {
        if ( !is_array( $params ) ) {
            $params = [];
        }

        if ( PHPExt::is_assoc( $values ) ) {
            foreach ( $values as $key => $val ) {
                $params[$key] = $val;
            }

            return ':' . join( ',:', array_keys( $values ) );
        }
        else {
            if ( !is_array( $values ) ) {
                $values = [ $values ];
            }

            foreach ( $values as $val ) {
                $params[] = $val;
            }

            return join( ',', array_fill( 0, count( $values ), '?' ) );
        }
    }

    /**
     * Sets the buffering mode for mysql.
     *
     * @param mixed   $dbh  Database handle.
     * @param boolean $flag Set buffered on or off.
     *
     * @since 1.0
     * @return boolean The old value.
     */
    public static function setBuffered( $dbh, $flag = true ) {
        if ( ( $dbh = self::connectionFactory( $dbh ) ) === false ) {
            return false;
        }

        $oldAttribute = self::$directQuery;
        if ( $oldAttribute != $flag ) {
            self::$directQuery = $flag;
        }

        return $oldAttribute;
    }

    /**
     * Sets the prepared emulation mode for mysql.
     *
     * @param mixed   $dbh  Database handle.
     * @param boolean $flag Set emulated prepared statements on or off.
     *
     * @since 1.0
     * @return boolean The old value.
     */
    public static function setPreparedMode( $dbh, $flag = true ) {
        if ( ( $dbh = self::connectionFactory( $dbh ) ) === false ) {
            return false;
        }

        $oldAttribute = self::$directQuery;
        self::$directQuery = $flag;

        return $oldAttribute;
    }

    /**
     * Returns a single value.
     *
     * Useful for count(*) sql calls.
     *
     * @param mixed  $dbh    Database handle.
     * @param string $qry    Query String.
     * @param mixed  $params Paraneters (array) or single parameter.
     *
     * @since 1.0
     * @return mixed
     */
    public static function oneValue( $dbh, $qry, $params = null ) {
        $row = self::oneRow( $dbh, $qry, $params, self::FETCH_NUM );
        if ( $row === false || !is_array( $row ) || $row[0] === null ) {
            return false;
        }
        else {
            return $row[0];
        }
    }

    /**
     * Returns a single row.
     *
     * @param mixed   $dbh       Database handle.
     * @param string  $qry       Query String.
     * @param mixed   $params    Paraneters (array) or single parameter.
     * @param integer $fetchType Fetch Type.
     *
     * @since 1.0
     * @return array
     */
    public static function oneRow( $dbh, $qry, $params = null, $fetchType = self::FETCH_ASSOC ) {
        $result = self::query( $dbh, $qry, $params );
        return ( $result === false ) ? false : $result->fetch( $fetchType );
    }

    /**
     * Returns a single column.
     *
     * @param mixed   $dbh       Database handle.
     * @param string  $qry       Query String.
     * @param mixed   $params    Paraneters (array) or single parameter.
     * @param integer $fetchType Fetch Type.
     *
     * @since 1.0
     * @return array
     */
    public static function oneColumn( $dbh, $qry, $params = null ) {
        $result = self::query( $dbh, $qry, $params );
        if ( $result === false ) {
            return false;
        }

        $col = [];
        while ( ( $row = $result->fetch( self::FETCH_NUM ) ) !== false ) {
            $col[] = $row[0];
        }

        if ( count( $col ) == 0 ) {
            return false;
        }

        return $col;
    }

    /**
     * Sets a database handle to unbuffered mode then makes a call then resets it back to buffered mode.
     *
     * This is good for one off calls, but if you are doing many updated you should call @see setBuffered
     * at the beginning of the loop and the again at the end.
     *
     * @param mixed  $dbh    Database handle.
     * @param string $qry    Query String.
     * @param mixed  $params Paraneters (array) or single parameter.
     *
     * @since 1.0
     * @return object Database resource
     */
    public static function unbuffered( $dbh, $qry, $params = null ) {
        $oldAttribute = self::setBuffered( $dbh, false );
        self::exec( $dbh, $qry, $params );
        self::setBuffered( $dbh, $oldAttribute );
        return $dbh;
    }

    public static function quote( $dbh, $value ) {
        if ( ( $dbResource = self::connectionFactory( $dbh ) ) === false ) {
            return $value;
        }

        return $dbResource->quote( $value );
    }
    /**
     * Executes a query and returns a PDOStatement that you can iterate over.
     *
     * @param mixed   $dbh    Database handle.
     * @param string  $qry    Query String.
     * @param mixed   $params Paraneters (array) or single parameter.
     * @param boolean $log    Force not log this call.
     *
     * @since 1.0
     * @return object Result set of the query
     */
    public static function query( $dbh, $qry, $params = null, $log = false ) {
        $qry = self::doReplacements( $qry );

        if ( self::$queryLogFlag || $log ) {
            self::dbg( $qry, $params );
        }

        if ( ( $dbResource = self::connectionFactory( $dbh ) ) === false ) {
            return false;
        }

        try {
            if ( $params == null ) {
                // turn on emulating prepared statements because mysql gets confused on some statements
                if ( !in_array( self::driver( $dbh ), [ self::SQLSERVER, self::SQLITE ] ) ) {
                    $dbResource->setAttribute( \PDO::ATTR_EMULATE_PREPARES, true );
                }
                $result = $dbResource->query( $qry );
                if ( ! in_array( self::driver( $dbh ), [ self::SQLSERVER, self::SQLITE ] ) ) {
                    $dbResource->setAttribute( \PDO::ATTR_EMULATE_PREPARES, false );
                }
                return ( $result === FALSE ) ? self::logError( $qry, $dbResource ) : $result;
            }
            else {
                $prepareParams = [];
                if ( self::$directQuery === true && ! in_array( self::driver( $dbh ), [ self::SQLSERVER, self::SQLITE ] ) ) {
                    $prepareParams[\PDO::MYSQL_ATTR_DIRECT_QUERY] = true;
                }
                if ( ( $dbResource = $dbResource->prepare( $qry, $prepareParams ) ) === false ) {
                    return self::logErrorMessage( 'Problem SQL: ' . $qry );
                }

                if ( !is_array( $params ) ) {
                    $params = [ $params ];
                }

                $result = $dbResource->execute( $params );

                return ( $result === FALSE ) ? self::logError( $qry, $dbResource ) : $dbResource;
            }
        }
        catch ( Exception $ex ) {
            return self::logError( 'E: ' . $ex->getMessage() . ': ' . $qry, $dbResource );
        }
    }

    /**
     * Executes an update, insert of delete.
     *
     * Returns the number of rows affected
     *
     * @param mixed   $dbh    Database handle.
     * @param string  $qry    Query String.
     * @param mixed   $params Paraneters (array) or single parameter.
     * @param boolean $log    Force not log this call.
     *
     * @since 1.0
     * @return integer
     */
    public static function exec( $dbh, $qry, $params = null, $log = false ) {
        $qry = self::doReplacements( $qry );
        if ( self::$queryLogFlag || $log ) {
            self::dbg( $qry, $params );
        }

        if ( ( $dbResource = self::connectionFactory( $dbh ) ) === false ) {
            return false;
        }

        try {
            if ( $params == null ) {
                $result = $dbResource->exec( $qry );
            }
            else {
                if ( ( $newResource = $dbResource->prepare( $qry ) ) === false ) {
                    return self::logError( $qry . ' ' . print_r( $params, true ), $dbResource );
                }
                else {
                    $dbResource = $newResource;
                }

                if ( !is_array( $params ) ) {
                    $params = [ $params ];
                }

                $result = $dbResource->execute( $params );
            }

            if ( $result === false ) {
                return self::logError( $qry . ' ' . print_r( $params, true ), $dbResource );
            }
            else if ( is_int( $result ) ) {
                return $result;
            }
            else {
                return $dbResource->rowCount();
            }
        }
        catch ( Exception $ex ) {
            return self::logError( 'E: ' . $ex->getMessage() . ': ' . $qry . ' ' . print_r( $params, true ), $dbResource );
        }
    }

    /**
     * Generates the Limit sql on the passed in query
     * @param string $sql
     * @param int $start
     * @param int $cnt
     * @return string the sql with the limit added
     */
    static function limit( $sql, $start, $cnt ) {

        // Check if we are already doing the limiting
        if ( strpos( strtoupper( $sql ), 'LIMIT' ) !== FALSE ) {
            return ( $sql );
        }

        return ( $sql . " LIMIT $start,$cnt" );
    }

    /**
     * Replaces any special strings in the query with the appropriate values.
     *
     * @param string $query
     *
     * @since 1.0
     * @return string
     * */
    private static function doReplacements( $query ) {
        return strtr( $query, [ '%%PRE%%' => Cfg::get( 'prefix', 'w_' ),
            '%%SQLENGINE%%' => Cfg::get( 'sql_tabletype', self::SQL_ENGINE ),
            '%%SQLCHARSET%%' => Cfg::get( 'sql_charset', self::SQL_CHARSET ) ] );
    }

    private static function logError( $qry, $resource ) {
        self::logErrorMessage( join( ':', $resource->errorInfo() ) . ':' . $qry );
        //echo ( join ( ':', $resource->errorInfo () ) . ':' . $qry );
        return false;
    }

    private static function logErrorMessage( $message ) {
        //echo $message . self::calculateCallLocation();
        self::$log->error( $message, self::calculateCallLocation() );
        return false;
    }

    private static function dbg( $qry, &$params = null ) {
        $msg = self::$callNumber . ':"' . $qry . '"';
        self::$callNumber ++;
        if ( $params != null ) {
            $msg .= ( is_array( $params ) ) ? join( ':', $params ) : $params;
        }
        self::$log->debug( $msg, self::calculateCallLocation() );
    }

    private static function calculateCallLocation() {
        $stack = debug_backtrace();
        $stackLength = count( $stack );
        for ( $origin = 1; $origin < $stackLength; $origin++ ) {
            if ( __FILE__ != $stack[$origin]['file'] ) {
                break;
            }
        }

        $fileLocation = basename( $stack[$origin]['file'] );
        $lineNumber = '(L:' . $stack[$origin]['line'] . ')';
        $origin ++;
        $calledFrom = ( ( isset( $stack[$origin]['class'] ) ) ? $stack[$origin]['class'] : '' ) .
                ( ( isset( $stack[$origin]['type'] ) ) ? $stack[$origin]['type'] : '' ) .
                ( ( isset( $stack[$origin]['function'] ) ) ? $stack[$origin]['function'] : '' );
        if ( $calledFrom == '' ) {
            $calledFrom = $fileLocation;
        }

        return $lineNumber . $calledFrom;
    }

    /**
     * Returns last_insert_id().
     *
     * @param mixed $dbh Database resource.
     *
     * @since 1.0
     * @return integer Last inset id
     */
    public static function lastInsertId( $dbh ) {
        if ( ( $dbResource = self::connectionFactory( $dbh ) ) === false ) {
            return false;
        }

        return $dbResource->lastInsertId();
    }

    /**
     * Resets the connections etc back to a fresh state.
     *
     * @since 1.0
     * @return void
     */
    public static function reset() {
        if ( isset( self::$connections ) AND is_array( self::$connections ) AND count( self::$connections ) > 0 ) {
            foreach ( self::$connections as $db => $connection ) {
                unset( self::$connections[$db] );
            }
        }
        else {
            self::$connections = [];
        }
    }

    public static function driver( $dbh = self::DEF ) {
        return Cfg::get( $dbh . '-driver' );
    }

}
