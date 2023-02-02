<?php

namespace Jackbooted\Util;

/**
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 */
class StringUtil extends \Jackbooted\Util\JB {

    public static function unitsFormat( $num, $units, $msg = '' ) {
        return $msg . ( ( $msg == '' ) ? '' : ' ' ) . $num . ' ' . $units . self::plural( $num );
    }

    public static function money( $amt, $dec=0 ) {
        if ( ! is_numeric( $amt ) ) {
            return 'N/A';
        }
        else {
            return '$' . number_format( $amt, $dec );
        }
    }

    public static function plural( $num ) {
        return ( $num == 1 ) ? '' : 's';
    }

}
