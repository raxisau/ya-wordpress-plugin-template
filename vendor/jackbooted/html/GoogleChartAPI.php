<?php

namespace Jackbooted\Html;

/**
 * Wrapper for Google Charts API
 * @copyright Confidential and copyright (c) 2024 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 *
 * http://code.google.com/apis/chart/docs/gallery/bar_charts.html
 * http://code.google.com/apis/chart/docs/chart_params.html
 */
class GoogleChartAPI extends \Jackbooted\Util\JB {

    private static $URL;

    public static function init() {
        if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) {
            self::$URL = 'https://chart.googleapis.com/chart?';
        }
        else {
            self::$URL = 'http://chart.googleapis.com/chart?';
        }
    }

    public static function create() {
        return new GoogleChartAPI ();
    }

    private $params = array();

    public function __construct() {

    }

    public function add( $key, $value ) {
        $this->params[$key] = $value;
        return $this;
    }

    public function autoValues( /* var args */ ) {
        $allValues = func_get_args();
        $combinedList = array();
        foreach ( $allValues as $values ) {
            $combinedList = array_merge( $values, $combinedList );
        }

        $max = (int) ceil( max( $combinedList ) );
        $min = (int) floor( min( $combinedList ) );
        $range = $max - $min;
        if ( abs( $range ) < 0.00001 ) {
            $min = 0;
            $range = $max - $min;
            if ( abs( $range ) < 0.00001 ) {
                return false;
            }
        }

        $valuesStrings = array();
        foreach ( $allValues as $values ) {
            foreach ( $values as $idx => $val ) {
                $values[$idx] = (int) ( ( $values[$idx] - $min ) * 100.0 / $range );
            }
            $valuesStrings[] = join( ',', $values );
        }

        $step = $range / 5.0;
        if ( $step > 3 ) {
            $step = (int) floor( $step );
        }

        return $this->add( 'chxr', "1,{$min},{$max},{$step}" )
                        ->add( 'chg', '0,10' )
                        ->add( 'chd', 't:' . join( '|', $valuesStrings ) );
    }

    public function __toString() {
        return Tag::img( self::$URL . http_build_query( $this->params ) );
    }

}
