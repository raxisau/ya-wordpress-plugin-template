<?php

namespace Jackbooted\Util;

use \Jackbooted\G;

/**
  /** Sess.php -
 *
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 */
class Sess extends \Jackbooted\Util\JB {

    public static function get( $key, $def = '' ) {
        if ( !isset( $_SESSION[G::SESS][$key] ) ) {
            return $def;
        }
        return $_SESSION[G::SESS][$key];
    }

    public static function set( $key, $val ) {
        $_SESSION[G::SESS][$key] = $val;
    }

    public static function unset( $key ) {
        unset( $_SESSION[G::SESS][$key] );
    }
}
