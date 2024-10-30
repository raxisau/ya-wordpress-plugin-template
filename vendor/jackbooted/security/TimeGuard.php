<?php
namespace Jackbooted\Security;

use \Jackbooted\Forms\Request;
use \Jackbooted\G;
use \Jackbooted\Html\JS;
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

/**
 * This class generates an encripted key that is used by API calls
 * It is limited to certain length of time, and locked into a user
 */
class TimeGuard extends \Jackbooted\Util\JB {

    const KEY = '_TG';
    const DELIM = '*,*';
    const NOGUARD = 'NO_TIME_GUARD';
    const EXPIRY = 86400; // 60 * 60 * 24; // One Day

    private static $log;
    private static $crypto;

    public static function init() {
        self::$log = Log4PHP::logFactory( __CLASS__ );
        self::$crypto = new Cryptography ();
    }

    public function __construct() {
        parent::__construct();
    }

    public static function get( $targetFile, $forceEncrypt = true ) {
        $unencryptedKey = join( self::DELIM, [ G::get( 'fldUser', 'GUEST' ),
            $_SERVER['HTTP_HOST'],
            $_SERVER['HTTP_USER_AGENT'],
            session_id(),
            $targetFile,
            time() ] );
        return self::$crypto->encrypt( $unencryptedKey, $forceEncrypt );
    }

    public static function js( $targetFile ) {
        $param = self::param( $targetFile );

        return JS::javaScript( "var tgUrlParam = '$param';" );
    }

    public static function url( $targetFile, $forceEncrypt = true ) {
        return $targetFile . '?' . self::param( $targetFile, $forceEncrypt );
    }

    public static function param( $targetFile, $forceEncrypt = true ) {
        $key = self::KEY;
        $u = self::get( $targetFile, $forceEncrypt );
        $val = urlencode( $u );
        return $key . '=' . $val;
    }

    public static function check() {
        if ( ( $val = Request::get( self::KEY ) ) == '' ) {
            return self::NOGUARD;
        }
        else {
            $values = explode( self::DELIM, $val );
            if ( count( $values ) != 6 ) {
                return 'Incorrect TimeGuard format';
            }
            else if ( $values[0] != G::get( 'fldUser', 'GUEST' ) ) {
                return 'The user has changed in the submission of this url';
            }
            else if ( $values[1] != $_SERVER['HTTP_HOST'] ) {
                return 'Host server has been compromised';
            }
            else if ( $values[2] != $_SERVER['HTTP_USER_AGENT'] ) {
                return 'Browser has been compromised';
            }
            else if ( $values[3] != session_id() ) {
                return 'PHP Session ID has been compromised';
            }
            else if ( strpos( $_SERVER['SCRIPT_NAME'], $values[4] ) === false ) {
                return 'URL has been reused for target file name';
            }
            else {
                $diff = time() - $values[5];
                if ( $diff < 0 || $diff > self::EXPIRY ) {
                    return 'URL has expired';
                }
                else {
                    return true;
                }
            }
        }
    }

}
