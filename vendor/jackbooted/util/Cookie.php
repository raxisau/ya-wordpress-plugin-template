<?php

namespace Jackbooted\Util;

use \Jackbooted\Config\Cfg;
use \Jackbooted\Security\Cryptography;

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
class Cookie extends \Jackbooted\Util\JB {

    const CRYPTO_KEY = 'hgfads8h4325h7676asdvbe2misaj9fdsa3r';

    private static $crypto;
    private static $timeout;

    public static function init() {
        self::$crypto = new Cryptography( self::CRYPTO_KEY );
        self::$timeout = 14 * 24 * 60 * 60;
    }

    /** Function to get a Cookie
     * @param $s The name of the Cookie
     * @public
     */
    public static function get( $s, $def = '' ) {
        if ( !isset( $_COOKIE[$s] ) ) {
            return $def;
        }

        return self::$crypto->decrypt( $_COOKIE[$s] );
    }

    /** Function to set a Cookie
     * @param $s The name of the Cookie
     * @param $val The value of the Cookie
     * @public
     */
    public static function set( $key, $val ) {
        setcookie( $key, self::$crypto->encrypt( $val ), time() + self::$timeout, Cfg::get( 'cookie_path', '/' ) );
    }

    /** Function to clear a Cookie
     * @param $s The name of the Cookie
     * @public
     */
    public static function clear( $key ) {
        self::set( $key, '' );
    }

}
