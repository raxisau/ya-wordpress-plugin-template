<?php

namespace Jackbooted\Security;

use \Jackbooted\Config\Cfg;
use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBMaintenance;
use \Jackbooted\Forms\Request;
use \Jackbooted\Util\Log4PHP;

/**
 * Cross-Site Request Forgery Guard
 *
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */
class CSRFGuard extends \Jackbooted\Util\JB {

    const EXPIRY = 604800; // 60 * 60 * 24 * 7;
    const KEY = '_CG';

    private static $log;

    public static function init() {
        self::$log = Log4PHP::logFactory( __CLASS__ );
    }

    public static function key() {
        $key = uniqid( '', true );

        if ( Cfg::get( 'jb_db', false ) ) {
            $id = DBMaintenance::dbNextNumber( DB::DEF, 'tblCrossSiteProtection' );
            $sql = 'INSERT INTO tblCrossSiteProtection VALUES(?,?,?)';
            DB::exec( DB::DEF, $sql, [ $id, $key, time() + self::EXPIRY ] );
        }
        else {
            \Jackbooted\Util\Sess::set( $key, true );
        }
        return $key;
    }

    public static function check() {
        // If the variable is not there then assume all good
        if ( ( $csrfKey = Request::get( CSRFGuard::KEY ) ) == '' ) {
            return true;
        }

        return self::valid( $csrfKey );
    }

    public static function valid( $key ) {
        if ( ! Cfg::get( 'jb_db', false ) ) {
            if ( \Jackbooted\Util\Sess::get( $key, false ) ) {
                \Jackbooted\Util\Sess::unset( $key );
                return true;
            }
            else {
                self::$log->error( 'CSRFGuard failed: ' . $key . ' not available ' . $_SERVER['SCRIPT_NAME'] );
                return false;
            }
        }
        else {
            $sql = 'SELECT COUNT(*) FROM tblCrossSiteProtection WHERE fldUniqueID=?';
            $cnt = DB::oneValue( DB::DEF, $sql, $key );
            if ( $cnt > 0 ) {
                $sql = 'DELETE FROM tblCrossSiteProtection WHERE fldUniqueID=? OR fldExpiryDate<?';
                DB::exec( DB::DEF, $sql, [ $key, time() ] );
                return true;
            }
            else {
                $sql = 'DELETE FROM tblCrossSiteProtection WHERE fldExpiryDate<?';
                DB::exec( DB::DEF, $sql, time() );
                self::$log->error( 'CSRFGuard failed: ' . $key . ' not available ' . $_SERVER['SCRIPT_NAME'] );
                return false;
            }
        }
    }
}
