<?php

namespace Jackbooted\Util;

/**
 * @copyright Confidential and copyright (c) 2024 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 */
class StringUtil extends \Jackbooted\Util\JB {

    public static function plural( $value, $num ) {
        return ( $num == 1 ) ? $value : Pluralizer::pluralize( $value );
    }

    public static function unitsFormat( $num, $units, $msg = '' ) {
        return $msg . ( ( $msg == '' ) ? '' : ' ' ) . $num . ' ' .  self::plural( $units, $num );
    }

    public static function money( $amt, $dec=0, $currencyChar='$' ) {
        if ( ! is_numeric( $amt ) ) {
            return 'N/A';
        }
        else {
            return $currencyChar . number_format( $amt, $dec );
        }
    }

    // This is used for text that has come from user input.
    public static function text( $msg ) {
        return htmlentities( $msg );
    }

    public static function number( $n ) {
        if( ! is_numeric( $n ) ) {
            return false;
        }

        foreach ( [
            '1000000000000' => 'Trillion',
               '1000000000' => 'Billion',
                  '1000000' => 'M',
                     '1000' => 'K', ] as $amt => $name ) {
            if ( $n >= $amt ) {
                $fmtNumber = number_format( $n / $amt, 2 );
                if ( substr( $fmtNumber, -3 ) == '.00' ) {
                    $fmtNumber = substr( $fmtNumber, 0, -3 );
                }
                return $fmtNumber . $name;
            }
        }
        return number_format( $n );
    }

    public static function percent( $numerator, $denominator ) {
        if ( $denominator == 0.0 ) return 'N/A';
        return number_format( $numerator * 100.0 / $denominator, 2 ) . '%';
    }
}
