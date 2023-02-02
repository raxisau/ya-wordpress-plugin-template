<?php

namespace Jackbooted;

/** GlobalFunctions.php - Common functions that are required by other systems
 *
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 */
class G extends \Jackbooted\Util\JB {

    const SESS   = 'JACKBOOTWEB12';
    const PREFS  = 'PREFS12';
    const LOGIN  = 'loggedIn';
    const CRYPTO = 'CRYPTO_KEY';
    const IV     = 'PredefinedJackbootedEncryptionKey12345678901234567';

    /** This method gets the preference based on a name returns empty string if the variable
     * does not exist
     * @param $s name of the name value pair
     * @returns String
     */
    public static function get( $s, $def = '' ) {
        if ( !isset( $_SESSION[self::SESS][self::PREFS] ) ) {
            return $def;
        }
        return $_SESSION[self::SESS][self::PREFS]->get( $s, $def );
    }

    public static function getUserID() {
        return self::get( 'fldUserID', '0' );
    }

    /** This method sets a preference
     * @param string $key name of the name value pair
     * @param mixed $val value to send to preferences
     * @returns void
     */
    public static function set( $key, $val, $persist = false, $type = 'DATA' ) {
        if ( !isset( $_SESSION[self::SESS][self::PREFS] ) ) {
            return;
        }
        $_SESSION[self::SESS][self::PREFS]->put( $key, $val, $type );
    }

    /** This method checks whether the current user can log in based on the required
     * access level
     * @param int $s The access level that we are checking must be in the
     *           tables tblUserType
     * @returns boolean
     */
    public static function accessLevel( $level ) {
        $uLevel = self::get( ( self::isLoggedIn() ) ? 'fldLevel' : 'accesslevel' );
        return intval( $uLevel ) <= intval( $level );
    }

    /** This method returns the login status as defined in the session object.
     * @returns boolean
     */
    public static function isLoggedIn() {
        if ( !isset( $_SESSION[self::SESS][self::LOGIN] ) ) {
            return false;
        }
        return $_SESSION[self::SESS][self::LOGIN];
    }

    /**
     * sets the flag to say if the user is logged in
     * @param boolean $flag true if user is logged in
     */
    public static function setLoggedIn( $flag ) {
        if ( !isset( $_SESSION[self::SESS] ) ) {
            $_SESSION[self::SESS] = [];
        }
        $_SESSION[self::SESS][self::LOGIN] = $flag;
    }

    public static function isSmartPhone() {
        $ua = strtolower( $_SERVER['HTTP_USER_AGENT'] );
        if ( stripos( $ua, 'iphone' ) !== false ) {
            return true;
        }
        else if ( stripos( $ua, 'android' ) !== false &&
                stripos( $ua, 'mobile' ) !== false ) {
            return true;
        }
        else {
            return false;
        }
    }

}
