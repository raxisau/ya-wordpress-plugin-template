<?php

namespace Jackbooted\Util;

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

/**
 * Simple logging
 */
class Log4PHP extends \Jackbooted\Util\JB {

    /**
     * Log all messages.
     */
    const ALL = 7;

    /**
     * Log any message more severe than trace.
     */
    const TRACE = 6;

    /**
     * Log any message more severe than debug (not trace).
     */
    const DEBUG = 5;

    /**
     * Log any message more severe than info (no debug or trace).
     */
    const INFO = 4;

    /**
     * Log any message more severe than warning (no info, debug trace).
     */
    const WARN = 3;

    /**
     * Log any message more severe than Error (no warn, info, debug trace).
     */
    const ERROR = 2;

    /**
     * Log only fatal messages.
     */
    const FATAL = 1;

    /**
     * No logging.
     */
    const OFF = 0;

    private static $prefix = [
        self::TRACE => 'TRACE',
        self::DEBUG => 'DEBUG',
        self::INFO  => 'INFO',
        self::WARN  => 'WARN',
        self::ERROR => 'ERROR',
        self::FATAL => 'FATAL'
    ];

    /**
     * Logging device to screen (seperated by <br>).
     */
    const SCREEN = 'S';

    /**
     * Logging device to text (phpunit style. Line breakes with \n).
     */
    const RAW = 'R';

    /**
     * Logging to phperror.log file.
     */
    const LOGFILE = 'L';

    /**
     * Logging to normal file in temp folder.
     */
    const FILE = 'F';

    /**
     * @var array used to keep a cache of the classes that have a logger
     * created for them.
     */
    private static $logList = [];

    /**
     * @var integer Keep the current log level.
     */
    private static $logLevel = self::ERROR;
    private static $outputDevice = self::FILE;
    private static $logFile = null;

    /**
     * Class level initialization.
     *
     * @since 1.0
     * @return void
     */
    public static function init( $logLevel = self::ALL ) {
        self::setLogLevel( $logLevel );
    }

    /**
     * Sets the logging level for all new log classes that are created.
     *
     * @param string $l Log level from the constanst aboove.
     *
     * @since 1.0
     * @return void
     */
    public static function setLogLevel( $l ) {
        self::$logLevel = $l;
    }

    /**
     * Set the default output device for logging.
     *
     * Can be one of the following:
     * Log4PHP::SCREEN, Log4PHP::RAW, Log4PHP::LOGFILE, Log4PHP::FILE,
     *
     * Example:
     * <pre>
     * require_once ( Config::get ( 'classes_dir' ) . '/util/Log4PHP.inc' );
     * if ( Config::get ( 'development') ) {
     *     Log4PHP::setLogLevel ( Log4PHP::ALL );
     *     Log4PHP::setOutput( Log4PHP::LOGFILE );
     * }
     * </pre>
     *
     * @param string $o Output device.
     *
     * @since 1.0
     * @return boolean True on success.
     */
    public static function setOutput( $o ) {
        self::$outputDevice = $o;
        return true;
    }

    /**
     * Pass in the class and it will create the Logger for it.
     *
     * @param string $className Class name for this logger.
     *
     * @since 1.0
     * @return Log4PHP
     */
    public static function logFactory( $className = null ) {
        if ( $className == null || !isset( self::$logList[$className] ) ) {
            if ( $className == null ) {
                $className = __CLASS__;
            }
            self::$logList[$className] = new Log4PHP( $className );
        }

        return self::$logList[$className];
    }

    /**
     * This looks for the old log files and removes them from the system.
     *
     * @param type $numDays Number of days to keep - defaults to 5
     */
    public static function cleanup( $numDays = 5 ) {
        $oneDay = 60 * 60 * 24;
        $removedFiles = 0;

        // Loop for 10 times looking for old files
        for ( $i = 0, $day = time() - ($numDays * $oneDay); $i < 10; $i++, $day -= $oneDay ) {
            $fileName = PHPExt::getTempDir() . '/Log4PHP-log-' . date( 'Y-m-d', $day ) . '.txt';

            // If the file exists then remove it
            if ( file_exists( $fileName ) ) {
                unlink( $fileName );
                $removedFiles ++;
            }
            else {
                // Stop the looping if the file does not exist
                break;
            }
        }
        return [ 0, "Removed: $removedFiles" ];
    }

    private $className = '';
    private $classErrorLevel;
    private $classOutputDevice;

    /**
     * Pass in the class name to construct.
     *
     * If the class is not passed in the
     * constructor it will attempt to figure it out. It is not recommended to call the
     * constructor directly. You are better using Log4PHP::logFactory ( $className )
     * This will cache the loggers so that there is only ever one per class.
     *
     * @param string $c Class name.
     *
     * @since 1.0
     */
    public function __construct( $c = null ) {
        parent::__construct();
        if ( $c == null ) {
            $stack = debug_backtrace();
            $pi = pathinfo( $stack[1]['file'] );
            $c = $pi['filename'];
        }

        $this->className = $c;
        $this->classErrorLevel = self::$logLevel;
        $this->classOutputDevice = self::$outputDevice;
    }

    /**
     * Allows you to change the error level for a single class instance.
     *
     * Useful for debugging. eg: @see setClassOutputDevice
     *
     * @param string $errorLevel Can be Log4PHP::ALL, Log4PHP::TRACE, Log4PHP::DEBUG,
     * Log4PHP::INFO, Log4PHP::WARN, Log4PHP::ERROR, Log4PHP::FATAL.
     *
     * @since 1.0
     * @return void
     */
    public function setClassErrorLevel( $errorLevel ) {
        $this->classErrorLevel = $errorLevel;
    }

    /**
     * Allows you to change the output device for a single class instance.
     *
     * Useful for debugging. A situation that this might be useful if you wanted to send all
     * Database activity to SCREEN. eg.
     * <pre>
     *  $dbLogger = DB::getLogger ();
     *  $dbLogger->setClassOutputDevice ( Log4PHP::SCREEN );
     *  $dbLogger->setClassErrorLevel ( Log4PHP::ALL );
     *  DB::init();
     * </pre>
     *
     * @param string $outputDevice Output device.
     *
     * @since 1.0
     * @return void
     */
    public function setClassOutputDevice( $outputDevice ) {
        $this->classOutputDevice = $outputDevice;
    }

    /**
     * Shows the error message to the appropriate device.
     *
     * @param integer $level  The error level that is being displayed.
     * @param string  $s      The message to display.
     * @param string  $source The source of the coller.
     *
     * @since 1.0
     * @return void
     */
    public function show( $level, $s, $source = '' ) {
        // Get out if the error level is too high
        if ( $level > $this->classErrorLevel ) {
            return;
        }

        $errLev = error_reporting( E_ALL | E_STRICT );

        // Remove extra spaces and add prefix
        $s = self::$prefix[$level] . ': ' . preg_replace( '/\s{2,}/', ' ', $s );

        if ( $source == '' ) {
            $stack = debug_backtrace();
            $funcName = ( isset( $stack[2]['function'] ) ) ? $stack[2]['function'] : 'NONE';
            $source = $this->className . '.' . $funcName;
        }

        // Append the class and function from the caller
        $msg = $source . '>' . $s;

        switch ( $this->classOutputDevice ) {
            case self::SCREEN:
                $this->messageToScreen( $msg );
                break;

            case self::RAW:
                echo $msg . "\n";
                break;

            case self::FILE:
                $this->messageToFile( $msg );
                break;

            case self::LOGFILE:
            default:
                error_log( str_replace( "\n", ' ', $msg ) );
                break;
        }

        error_reporting( $errLev );
    }

    /**
     * Appends the message out to log file.
     *
     * @param string $msg Message to log.
     *
     * @since 1.0
     * @return void
     */
    private function messageToFile( $msg ) {
        if ( self::$logFile == null ) {
            self::$logFile = fopen( PHPExt::getTempDir() . '/Log4PHP-log-' . date( 'Y-m-d' ) . '.txt', 'a' );
        }

        fwrite( self::$logFile, date( 'Y-m-d H:i:s' ) . ' ' . str_replace( "\n", ' ', $msg ) . "\n" );
    }

    /**
     * Appends the message out to log file.
     *
     * @param string $msg Message to log.
     *
     * @since 1.0
     * @return void
     */
    private function messageToScreen( $msg ) {
        echo date( 'Y-m-d H:i:s' ) . ' ' . str_replace( "\n", ' ', $msg ) . "<br/>\n";
    }

    /**
     * Log a trace message.
     *
     * @param string $msg    Message to log.
     * @param string $source Source of the log call.
     *
     * @since 1.0
     * @return void
     */
    public function trace( $msg, $source = '' ) {
        $this->show( self::TRACE, $msg, $source );
    }

    /**
     * Log a debug message.
     *
     * @param string $msg    Message to log.
     * @param string $source Source of the log call.
     *
     * @since 1.0
     * @return void
     */
    public function debug( $msg, $source = '' ) {
        $this->show( self::DEBUG, $msg, $source );
    }

    /**
     * Log an info message.
     *
     * @param string $msg    Message to log.
     * @param string $source Source of the log call.
     *
     * @since 1.0
     * @return void
     */
    public function info( $msg, $source = '' ) {
        $this->show( self::INFO, $msg, $source );
    }

    /**
     * Log a warning message.
     *
     * @param string $msg    Message to log.
     * @param string $source Source of the log call.
     *
     * @since 1.0
     * @return void
     */
    public function warn( $msg, $source = '' ) {
        $this->show( self::WARN, $msg, $source );
    }

    /**
     * Log an error message.
     *
     * @param string $msg    Message to log.
     * @param string $source Source of the log call.
     *
     * @since 1.0
     * @return void
     */
    public function error( $msg, $source = '' ) {
        $this->show( self::ERROR, $msg, $source );
    }

    /**
     * Log a fatal message.
     *
     * @param string $msg    Message to log.
     * @param string $source Source of the log call.
     *
     * @since 1.0
     * @return void
     */
    public function fatal( $msg, $source = '' ) {
        $this->show( self::FATAL, $msg, $source );
    }

    /**
     * Returns true if the passed level would be displayed.
     *
     * This method would be used
     * to save some processing.
     * Returns True if the error message would be displayed.
     *
     * @param integer $level The level that we are testing.
     *
     * @since 1.0
     * @return boolean
     */
    public function isDisplayed( $level ) {
        return $level <= $this->classErrorLevel;
    }

}
