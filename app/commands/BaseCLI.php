<?php
namespace App\Commands;

class BaseCLI extends \Jackbooted\Html\WebPage {
    const SEND_LATER = 'SEND_LATER';

    protected static $debug         = FALSE;
    protected static $debugMore     = FALSE;
    protected static $simulation    = FALSE;
    protected static $forcedUpdate  = FALSE;
    protected static $silentOutput  = FALSE;
    protected static $displayHelp   = FALSE;
    protected static $opsEmail      = 'Operations Manager<ops.manager@dropcatcher.com.au>';
    protected static $emailList     = null;
    protected static $baseHelp      = <<<TXT
            Options:
            -v   - Turn on Debug
            -vv  - Turn on More Debug
            -sim - Simulate. Do not do any database updates
            -s   - Silent
            -u   - Force update/actions even if config does not allow
            -h   - This message
    TXT;

    public static function init () {
        parent::init();

        date_default_timezone_set( 'UTC' );

        if ( isset( $_SERVER['argv'] ) && is_array( $_SERVER['argv'] ) ) {
            self::$debug        = in_array( '-v',   $_SERVER['argv'] ) ||
                                  in_array( '-vv',  $_SERVER['argv'] );
            self::$debugMore    = in_array( '-vv',  $_SERVER['argv'] );
            self::$simulation   = in_array( '-sim', $_SERVER['argv'] );
            self::$forcedUpdate = in_array( '-u',   $_SERVER['argv'] );
            self::$silentOutput = in_array( '-s',   $_SERVER['argv'] );
            self::$displayHelp  = in_array( '-h',   $_SERVER['argv'] );
        }

        self::$emailList    = join( ',', [ self::$opsEmail ]);
        if ( self::$debug ) {
            \App\App::debug();
        }
    }


    protected static function echoD ( $msg ) {
        if ( self::$debug ) {
            echo $msg;
        }
    }

    protected function JSON( $results ) {
        if ( self::$silentOutput && count( $results['error'] ) == 0 ) {
            return '';
        }
        else {
            $jsonOpts = ( self::$debug ) ? JSON_PRETTY_PRINT : 0;
            return json_encode( $results, $jsonOpts ) . "\n";
        }
    }

    protected static function arg( $searchToken ) {
        if ( ( $pos = array_search( $searchToken, $_SERVER['argv'] ) ) !== FALSE ) {
            $pos ++;
            if ( isset( $_SERVER['argv'][$pos] ) ) {
                return $_SERVER['argv'][$pos];
            }
        }
        return false;
    }

    protected function needHelp() {
        if ( self::$displayHelp ) {
            echo self::$baseHelp;
            return true;
        }
        else {
            return false;
        }
    }
}

