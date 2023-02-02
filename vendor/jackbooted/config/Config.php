<?php

namespace Jackbooted\Config;

use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBMaintenance;
use \Jackbooted\G;

/**
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 *
 * Example
 * //Username to use for SMTP authentication
 * $this->Username = Config::get( 'gmail.smtp.username', 'brettcraigdutton@gmail.com' );
 *
 */
class Config extends \Jackbooted\Util\JB {

    // In memory cache of configuration items
    private static $configItemsObjects = [];
    private static $overrideScope = false;
    private static $haveDB = true;

    const INSERT_SQL = "REPLACE INTO tblConfig (fldConfigID,fldUserID,fldKey,fldValue) VALUES(?,?,?,?)";
    const SELECT_SQL_ID = "SELECT fldConfigID FROM tblConfig WHERE fldKey=? AND fldUserID=?";
    const SELECT_SQL = "SELECT fldValue FROM tblConfig WHERE fldKey=? AND fldUserID=?";
    const GLOBAL_SCOPE = 'GLOBAL';
    const USER_SCOPE = 'USER';

    public static function setOverrideScope( $scope = false ) {
        self::$overrideScope = $scope;
    }

    public static function setHaveDB( $doWeHaveDB ) {
        self::$haveDB = $doWeHaveDB;
    }

    public static function get( $key, $def = '', $scope = self::USER_SCOPE ) {
        if ( isset( self::$configItemsObjects[$key] ) ) {
            return self::$configItemsObjects[$key];
        }
        else {
            self::getFromDB( $key, $scope );
            if ( isset( self::$configItemsObjects[$key] ) ) {
                return self::$configItemsObjects[$key];
            }
            else {
                self::$configItemsObjects[$key] = $def;
                if ( $def != '' ) {
                    self::putIntoDB( $key, $def, $scope );
                }
                return $def;
            }
        }
    }

    public static function put( $key, $value, $scope = self::USER_SCOPE ) {
        self::$configItemsObjects[$key] = $value;
        self::putIntoDB( $key, $value, $scope );
    }

    private static function getScope( $scope = self::USER_SCOPE ) {
        if ( self::$overrideScope ) {
            $uid = self::$overrideScope;
        }
        else if ( $scope == self::USER_SCOPE ) {
            $uid = G::get( 'fldUserID', self::GLOBAL_SCOPE );
        }
        else {
            $uid = self::GLOBAL_SCOPE;
        }
        return $uid;
    }

    private static function putIntoDB( $key, $value, $scope = self::USER_SCOPE ) {
        if ( ! self::$haveDB ) {
            return;
        }

        $uid = self::getScope( $scope );

        if ( ( $id = DB::oneValue( DB::DEF, self::SELECT_SQL_ID, [ $key, $uid ] ) ) === false ) {
            $id = DBMaintenance::dbNextNumber( DB::DEF, 'tblConfig' );
        }

        DB::exec( DB::DEF, self::INSERT_SQL, [ $id,
            $uid,
            $key,
            json_encode( $value ) ] );
    }

    private static function getFromDB( $key, $scope = self::USER_SCOPE ) {
        if ( ! self::$haveDB ) {
            return;
        }

        $uid = self::getScope( $scope );

        if ( ( $serializedValue = DB::oneValue( DB::DEF, self::SELECT_SQL, [ $key, $uid ] ) ) !== false ) {
            self::$configItemsObjects[$key] = json_decode( $serializedValue, true );
        }
        else if ( $uid !== self::GLOBAL_SCOPE ) {
            if ( ( $serializedValue = DB::oneValue( DB::DEF, self::SELECT_SQL, [ $key, self::GLOBAL_SCOPE ] ) ) !== false ) {
                self::$configItemsObjects[$key] = json_decode( $serializedValue, true );
                return true;
            }
        }
    }

    public static function clearCache() {
        self::$configItemsObjects = [];
    }

}
