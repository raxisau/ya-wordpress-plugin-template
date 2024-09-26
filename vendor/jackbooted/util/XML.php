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
 *
 */
class XML extends \Jackbooted\Util\JB {
    public static function beautify( $unformattedXML ) {
        $dom = new \DOMDocument( '1.0' );
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML( $unformattedXML );
        return $dom->saveXML();
    }

    public static function optimise( $xml ) {
        $pattern = [ '/^\s+/m', '/\s+$/m', '/\n/' ];
        $replace = [ '',        '',        '' ];
        return preg_replace( $pattern, $replace, $xml );
    }

    public static function normalize( $rawString ) {
        $badChars  = [ "&#039;", "’", chr( 146 ) ]; // See scratchpad/characterReplace.php to see the strange character for 146
        $goodChars = [ "'",      "'", "'" ];

        $xmlString = htmlspecialchars( str_replace( $badChars, $goodChars, $rawString ) );

        return $xmlString;
    }
    public static function toPHP( $xmlString, $repeaters=[], $starting='' ) {
        $dom = new \DOMDocument( '1.0' );
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML( $xmlString );

        $nodeList = ( $starting == '' ) ? $dom->childNodes : $dom->getElementsByTagName( $starting );

        return self::_xml2phpHelper( $nodeList, $repeaters );
    }

    private static function _xml2phpHelper( $nodeList, $repeaters ) {

        if ( $nodeList->count() == 1 && ! $nodeList->item( 0 )->hasChildNodes() ) {
            return $nodeList->item( 0 )->nodeValue;
        }

        $arr = [];
        for ( $i=0; $i<$nodeList->count(); $i++ ) {
            $node = $nodeList->item( $i );

            $name = $node->nodeName;
            $data = ( $node->childNodes->count() > 0 ) ? self::_xml2phpHelper( $node->childNodes, $repeaters ) : $node->nodeValue;

            // If this is specified as a repeater then ensure that it is an array
            if ( in_array( $name, $repeaters ) ) {
                if ( ! isset( $arr[$name] ) ) {
                    $arr[$name] = [];
                }
                $arr[$name][] = $data;
            }
            else if ( ! isset( $arr[$name] ) ) {
                $arr[$name] = $data;
            }
            else {
                // This is checking for a repeating element that we didn't specify
                // If it is repeating then will convert into an array
                if ( is_array( $arr[$name] ) && isset( $arr[$name][0] ) ) {
                    $arr[$name][] = $data;
                }
                else {
                    $arr[$name] = [ $arr[$name], $data ];
                }
            }
        }

        return $arr;
    }
}

