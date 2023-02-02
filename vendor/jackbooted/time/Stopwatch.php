<?php

namespace Jackbooted\Time;

use \Jackbooted\Util\Log4PHP;
use \Jackbooted\Util\StringUtil;

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
 * Stopwatch
 */
class Stopwatch extends \Jackbooted\Util\JB {
    const ONE_MINUTE =     60;
    const ONE_HOUR   =   3600; // 60 * 60
    const ONE_DAY    =  86400; // 60 * 60 * 24
    const ONE_WEEK   = 604800; // 60 * 60 * 24 * 7

    private static $log;
    private $startTime;
    private $msg;

    public static function init() {
        self::$log = Log4PHP::logFactory( __CLASS__ );
    }

    public function __construct( $msg, $displayStart = true ) {
        parent::__construct();
        $this->msg = $msg;
        $this->start( $displayStart );
    }

    private function start( $displayStart ) {
        $this->startTime = self::begin();
        if ( $displayStart ) {
            self::$log->debug( $this->msg );
        }
        return $this->startTime;
    }

    public function stop() {
        $delta = self::end( $this->startTime );
        self::$log->debug( $this->msg . ':' . $this->msToStr( $delta ) );
        return $this->msg . ':' . $this->msToStr( $delta );
    }

    public function getTime() {
        return self::end( $this->startTime );
    }

    public function logLoadTime() {
        return $this->stop();
    }

    public static function getLog() {
        return self::$log;
    }

    public static function begin() {
        return microtime( true );
    }

    public static function end( $startTime ) {
        return microtime( true ) - $startTime;
    }

    public static function timeToDB( $time = false ) {
        if ( $time === false ) {
            $time = time();
        }
        return date( 'Y-m-d H:i:s', $time );
    }

    public static function dateToDB( $time = false ) {
        if ( $time === false ) {
            $time = time();
        }
        return date( 'Y-m-d', $time );
    }

    public static function msToStr( $delta ) {
        $ms = $delta * 1000;
        $sec = intval( $ms / 1000 );
        $ms = intval( (int)$ms % 1000 );

        $msg = self::secToStr( $sec );
        $msg .= ( ( $msg == '' ) ? '' : ' ' ) . $ms . ' ms';

        return $msg;
    }

    public static function secToStr( $sec ) {
        $min = intval( $sec / 60 );
        $sec %= 60;
        $hr = intval( $min / 60 );
        $min %= 60;
        $day = intval( $hr / 24 );
        $hr %= 60;

        $msg = '';
        if ( $day != 0 ) {
            $msg = StringUtil::unitsFormat( $day, 'day', $msg );
        }
        if ( $hr != 0 ) {
            $msg = StringUtil::unitsFormat( $hr, 'hour', $msg );
        }
        if ( $min != 0 ) {
            $msg = StringUtil::unitsFormat( $min, 'min', $msg );
        }
        if ( $sec != 0 ) {
            $msg = StringUtil::unitsFormat( $sec, 'sec', $msg );
        }
        return $msg;
    }

    public static function secToDays( $sec ) {
        $min = intval( $sec / 60 );
        $sec %= 60;
        $hr = intval( $min / 60 );
        $min %= 60;
        $day = intval( $hr / 24 );
        $hr %= 24;

        return $day;
    }
}
