<?php

namespace Jackbooted\Security;

use \Jackbooted\Admin\Admin;
use \Jackbooted\Config\Cfg;
use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBTable;
use \Jackbooted\Forms\Request;
use \Jackbooted\G;
use \Jackbooted\Html\WebPage;
use \Jackbooted\Util\Log4PHP;

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
class Privileges extends \Jackbooted\Util\JB {

    private static $cache = [];
    private static $log;
    private static $securityLevels = null;

    public static function init() {
        self::$log = new Log4PHP( __CLASS__ );
    }

    public static function access( $action = null ) {
        if ( !Cfg::get( 'check_priviliages' ) ) {
            return true;
        }

        if ( $action == null ) {
            $action = Request::get( WebPage::ACTION );
        }

        if ( isset( self::$cache[$action] ) ) {
            return self::$cache[$action];
        }

        if ( ( $priviliagesIDs = self::getPriviliageIDs( $action ) ) === false ) {
            self::$log->warn( 'No priviliages found for action: ' . $action );
            return self::$cache[$action] = true;
        }

        $uid = G::get( 'fldUserID', '0' );
        $groupIDs = self::getGroupIDs( $uid );
        $params = [];
        $privIdIn = DB::in( $priviliagesIDs, $params );
        $params[] = $uid;
        $params[] = (int) G::get( 'fldLevel', 7 );
        $groupIn = DB::in( $groupIDs, $params );

        $now = time();

        $sql = <<<SQL
            SELECT count(*) FROM tblSecPrivUserMap
            WHERE fldPrivilegeID IN ( $privIdIn )
            AND   ( fldStartDate=0 OR fldStartDate < $now )
            AND   ( fldEndDate=0   OR fldEndDate > $now )
            AND   ( ( fldUserID  IS NOT NULL AND fldUserID<>''  AND fldUserID=? )  OR
                    ( fldLevelID IS NOT NULL AND fldLevelID<>'' AND fldLevelID>=? )  OR
                      fldGroupID IN ( $groupIn ) )
SQL;

        if ( DB::oneValue( DB::DEF, $sql, $params ) > 0 ) {
            return self::$cache[$action] = true;
        }

        return self::canLogin( $priviliagesIDs );
    }

    private static function canLogin( $priviliagesIDs ) {
        $privIdIn = DB::in( $priviliagesIDs );
        $now = time();

        $sql = <<<SQL
            SELECT fldLoginAction FROM tblSecPrivUserMap
            WHERE fldPrivilegeID IN ( $privIdIn )
            AND   ( fldStartDate=0 OR fldStartDate < $now )
            AND   ( fldEndDate=0   OR fldEndDate > $now )
            AND     fldLevelID IS NOT NULL
            AND     fldLevelID <> ''
            AND     fldLoginAction IS NOT NULL
            AND     fldLoginAction <> ''
SQL;

        return DB::oneValue( DB::DEF, $sql, $priviliagesIDs );
    }

    private static function getGroupIDs( $uid ) {
        $qry = 'SELECT fldGroupID FROM tblUserGroupMap WHERE fldUserID=?';
        $groups = DBTable::factory( DB::DEF, $qry, $uid, DB::FETCH_NUM )->getColumn( 0 );
        $groups[] = DB::oneValue( DB::DEF, 'SELECT fldGroupID FROM tblGroup LIMIT 1' );
        return $groups;
    }

    private static function getPriviliageIDs( $action ) {
        $sql = 'SELECT fldSecPrivilegesID FROM tblSecPrivileges WHERE ? LIKE fldAction';
        $tab = new DBTable( DB::DEF, $sql, $action, DB::FETCH_NUM );
        if ( $tab->isEmpty() ) {
            return false;
        }
        return $tab->getColumn( 0 );
    }



    public static function getSecurityLevel( $level ) {
        if ( self::$securityLevels == null ) {
            self::$securityLevels = [
                0                => 'GOD',
                1                => 'SUPER ADMIN',
                2                => 'SITE ADMIN',
                3                => 'MANAGER',
                4                => 'ASSIST MANAGER',
                5                => 'STAFF',
                6                => 'USER',
                7                => 'GUEST',
                'GOD'            => 0,
                'SUPER ADMIN'    => 1,
                'SITE ADMIN'     => 2,
                'MANAGER'        => 3,
                'ASSIST MANAGER' => 4,
                'STAFF'          => 5,
                'USER'           => 6,
                'GUEST'          => 7,
            ];
        }
        return self::$securityLevels[$level];
    }

}
