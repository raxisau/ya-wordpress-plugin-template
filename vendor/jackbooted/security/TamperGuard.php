<?php
namespace Jackbooted\Security;

use \Jackbooted\Config\Cfg;
use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\Util\Log4PHP;

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
class TamperGuard extends \Jackbooted\Util\JB {

    const CHECKSUM = '_CS';

    private static $log;
    public static $knownFields;

    public static function init() {
        self::$log = Log4PHP::logFactory( __CLASS__ );
        self::$knownFields = [ 'XDEBUG_SESSION_START', 'XDEBUG_PROFILE' ];
    }

    public function __construct() {
        parent::__construct();
    }

    public static function known( $key ) {
        self::$knownFields[] = $key;
    }

    public static function del( Response $response ) {
        $response->del( self::CHECKSUM );
    }

    public static function add( Response $response ) {
        $keyList = [];
        $valList = [];

        foreach ( $response as $key => $val ) {
            if ( is_array( $val ) ) {
                foreach ( $val as $key1 => $val1 ) {
                    if ( is_array( $val1 ) ) {
                        continue;
                    }
                    $keyList[] = '[' . $key . '][' . $key1 . ']';
                    $valList[] = $val1;
                }
            }
            else {
                if ( ( $loc = strpos( $key, '[' ) ) !== false ) {
                    $keyList[] = '[' . substr( $key, 0, $loc ) . ']' . substr( $key, $loc );
                }
                else {
                    $keyList[] = '[' . $key . ']';
                }
                $valList[] = $val;
            }
        }

        $flatKeyList = join( ',', $keyList );
        $hash = md5( $flatKeyList . join( '', $valList ) );
        $response->set( self::CHECKSUM, [ $flatKeyList, $hash ] );
    }

    public static function check( Request $request ) {
        if ( ( $formVarLen = $request->count() ) == 0 ) {
            return true;
        }

        foreach ( $request as $key => $val ) {
            if ( in_array( $key, self::$knownFields ) ) {
                $formVarLen --;
            }
        }

        if ( $formVarLen <= 0 ) {
            return true;
        }

        if ( ( $checksum = $request->getVar( self::CHECKSUM ) ) == '' ) {
            $request->clear();
            if ( Cfg::get( 'jb_tamper_detail', false ) ) {
                return 'Checksum Variable Missing from the request.';
            }
            else {
                self::$log->error( 'Checksum Variable Missing from the request: ' . $_SERVER['SCRIPT_NAME'] );
                return false;
            }
        }

        if ( !is_array( $checksum ) ) {
            $request->clear();
            if ( Cfg::get( 'jb_tamper_detail', false ) ) {
                return 'Checksum Variable not an array.';
            }
            else {
                self::$log->error( 'Checksum Variable not an array: ' . $_SERVER['SCRIPT_NAME'] );
                return false;
            }
        }

        if ( count( $checksum ) != 2 ) {
            $request->clear();
            if ( Cfg::get( 'jb_tamper_detail', false ) ) {
                return 'Checksum Variable not 2 elements.';
            }
            else {
                self::$log->error( 'Checksum Variable not 2 elements: ' . $_SERVER['SCRIPT_NAME'] );
                return false;
            }
        }

        if ( !empty( $checksum[0] ) ) {
            $allVariablesJoined = $checksum[0];
            foreach ( explode( ',', $checksum[0] ) as $key ) {
                $val = $request->getRaw( $key );
                $allVariablesJoined .= $val;
            }
        }
        else {
            $allVariablesJoined = '';
        }

        if ( md5( $allVariablesJoined ) != $checksum[1] ) {
            $request->clear();
            if ( Cfg::get( 'jb_tamper_detail', false ) ) {
                return 'Checksum failed md5(' . $allVariablesJoined . ')<>' . $checksum[1];
            }
            else {
                self::$log->error( 'The checksum has failed. The request variables have been tampered: ' . $_SERVER['SCRIPT_NAME'] );
                return false;
            }
        }

        return true;
    }

}
