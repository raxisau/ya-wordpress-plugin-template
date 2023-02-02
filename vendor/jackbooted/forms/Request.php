<?php

namespace Jackbooted\Forms;

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
 * Class for Managing Form Variables
 */
class Request extends PipeLine {

    private static $defaultInstance = null;
    public static $log;

    public static function init() {
        self::$log = \Jackbooted\Util\Log4PHP::logFactory( __CLASS__ );
        self::$defaultInstance = new Request ( );
    }

    /**
     * Gets the current form variables. This is called with Request::get because
     * we want to get a pointer, not a copy. The form variables and query
     * string variables are considered the same
     * @param  $key
     * @param  $def
     * @return ?#M#P#CRequest.defaultInstance.getVar
     */
    public static function get( $key = null, $def = null ) {
        return self::$defaultInstance->getVar( $key, $def );
    }

    public static function dmp( ) {
        return self::$defaultInstance->dump();
    }

    /**
     * Save the value into the session
     * @param  $key
     * @param  $val
     * @return void
     */
    public static function set( $key, $val ) {
        self::$defaultInstance->setVar( $key, $val );
    }

    public static function check() {
        return \Jackbooted\Security\TamperGuard::check( self::$defaultInstance );
    }

    /**
     * @param  $varsToProcess
     * @return void
     */
    public function __construct( &$varsToProcess = null ) {
        parent::__construct();
        if ( $varsToProcess == null ) {
            $this->formVars = $this->getRequestVars();
        }
        else if ( is_array( $varsToProcess ) ) {
            $this->formVars = $varsToProcess;
        }
        else if ( is_string( $varsToProcess ) ) {
            $this->formVars = $this->convertQueryStringToArray( $varsToProcess );
        }

        $this->decryptRequestVars( $this->formVars );
    }

    private function decryptRequestVars( &$arr ) {
        foreach ( $arr as $key => $val ) {
            if ( is_string( $arr[$key] ) ) {
                $arr[$key] = \Jackbooted\Security\Cryptography::de( $arr[$key] );
            }
            else if ( is_array( $arr[$key] ) ) {
                $this->decryptRequestVars( $arr[$key] );
            }
        }
    }

    private function getRequestVars() {
        $vars = array_merge( $_GET, $_POST );
        $this->removeHtml( $vars );
        return $vars;
    }

    private function removeHtml( &$arr ) {
        foreach ( $arr as $key => $val ) {
            if ( is_string( $arr[$key] ) ) {
                $arr[$key] = htmlspecialchars_decode( $arr[$key] );
            }
            else if ( is_array( $arr[$key] ) ) {
                $this->removeHtml( $arr[$key] );
            }
        }
    }

    private function convertQueryStringToArray( $queryString ) {
        $rawArray = explode( '&', $queryString );
        $qArray = [];
        foreach ( $rawArray as $element ) {
            list ( $key, $val ) = explode( '=', $element );
            $qArray[$key] = urldecode( $val );
        }
        return $qArray;
    }

    /**
     * Gets the current form variables. This is called with Request::get because
     * we want to get a pointer, not a copy. The form variables and query
     * string variables are considered the same
     * @param  $key
     * @param  $def
     * @return array|string
     */
    public function getVar( $key = null, $def = null ) {
        // if no key then give them the lot
        if ( $key === null ) {
            return $this->formVars;
        }

        if ( isset( $this->formVars[$key] ) ) {
            return $this->formVars[$key];
        }
        else if ( $def === null ) {
            return '';
        }
        else {
            $this->setVar( $key, $def );
            return $def;
        }
    }

    /**
     * @param  $key
     * @param  $val
     * @return void
     */
    public function setVar( $key, $val ) {
        $this->formVars[$key] = $val;
    }
}