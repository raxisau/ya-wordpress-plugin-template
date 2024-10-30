<?php

namespace Jackbooted\DB;

use \Jackbooted\Admin\Admin;
use \Jackbooted\Config\Cfg;
use \Jackbooted\Forms\CRUD;
use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\G;
use \Jackbooted\Html\Lists;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\WebPage;
use \Jackbooted\Security\Privileges;
use \Jackbooted\Util\CSV;
use \Jackbooted\Util\Module;
use \Jackbooted\Util\XLS;

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
class DBManager extends WebPage {

    const TABLES_SQL = 'SELECT fldTable FROM tblNextNumber ORDER BY fldTable';

    public static function menu() {
        if ( Privileges::access( __METHOD__ ) !== true || !G::isLoggedIn() ) {
            return '';
        }

        $html = Tag::hTag( 'b' ) . 'Database Menu' . Tag::_hTag( 'b' ) .
                Tag::form( [ 'method' => 'get' ] ) .
                Response::factory()->action( __CLASS__ . '->index()' )->toHidden( false ) .
                Lists::select( 'tblName', self::TABLES_SQL, [ 'size' => '10',
                    'onClick' => 'submit();' ] ) .
                Tag::_form();

        return $html;
    }

    public function index( $tName = '' ) {
        if ( ( $tableName = Request::get( 'tblName', $tName ) ) == '' ) {
            return '';
        }

        $crud = CRUD::factory( $tableName, [ 'topPager' => false ] )
                ->copyVarsFromRequest( 'tblName' );

        if ( preg_match( '/^tblMod([A-Z]+[a-z]+)/', $tableName, $matches ) ) {
            foreach ( Cfg::get( 'modules', [] ) as $moduleClass ) {
                eval( $moduleClass . '::' . Module::CRUD_MOD . '($crud);' );
            }
        }
        else {
            switch ( $tableName ) {
                case 'tblNextNumber':
                    $crud->setColDisplay( 'fldTable', [ CRUD::SELECT, DBMaintenance::getTableList(), true ] );
                    break;

                case 'tblSecPrivUserMap':
                    $userSql = ( DB::driver() == DB::MYSQL ) ? Admin::USER_SQL_MYSQL : Admin::USER_SQL_MYSQL;
                    $crud->setColDisplay( 'fldUserID', [ CRUD::SELECT, $userSql, true ] );
                    $crud->setColDisplay( 'fldGroupID', [ CRUD::SELECT, Admin::GROUP_SQL, true ] );
                    $crud->setColDisplay( 'fldPrivilegeID', [ CRUD::SELECT, Admin::PRIV_SQL, true ] );
                    $crud->setColDisplay( 'fldLevelID', [ CRUD::SELECT, Admin::LEVEL_SQL ] );
                    break;

                case 'tblUserGroupMap':
                    $userSql = ( DB::driver() == DB::MYSQL ) ? Admin::USER_SQL_MYSQL : Admin::USER_SQL_SQLITE;
                    $crud->setColDisplay( 'fldUserID', [ CRUD::SELECT, $userSql, true ] );
                    $crud->setColDisplay( 'fldGroupID', [ CRUD::SELECT, Admin::GROUP_SQL, true ] );
                    break;

                case 'tblUser':
                    $crud->setColDisplay( 'fldLevel', [ CRUD::SELECT, Admin::LEVEL_SQL ] );
                    $crud->setColDisplay( 'fldTimeZone', [ CRUD::SELECT, Admin::TZ_SQL ] );
                    break;
            }
        }

        $resp = Response::factory()->set( 'tblName', $tableName );

        return Tag::hTag( 'b' ) . 'Editing Table: ' . $tableName . Tag::_hTag( 'b' ) . ' ' .
                Tag::hRef( 'ajax.php?' . $resp->action( __CLASS__ . '->csv()' ), 'CSV' ) . ' ' .
                Tag::hRef( 'ajax.php?' . $resp->action( __CLASS__ . '->xls()' ), 'XLS' ) .
                $crud->index();
    }

    public function csv( $tName = '' ) {
        if ( ( $tableName = Request::get( 'tblName', $tName ) ) == '' )
            exit;
        CSV::output( DB::query( DB::DEF, 'SELECT * FROM ' . $tableName ), $tableName );
    }

    public function xls( $tName = '' ) {
        if ( ( $tableName = Request::get( 'tblName', $tName ) ) == '' ) {
            exit;
        }
        XLS::output( DB::query( DB::DEF, 'SELECT * FROM ' . $tableName ), $tableName );
    }

}
