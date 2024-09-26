<?php

namespace Jackbooted\Admin;

use \Jackbooted\Config\Cfg;
use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBMaintenance;
use \Jackbooted\DB\DBTable;
use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\Forms\Grid;
use \Jackbooted\G;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\WebPage;
use \Jackbooted\Security\Privileges;
use \Jackbooted\Util\PHPExt;

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
class SuperAdmin extends WebPage {

    const DEF = '\Jackbooted\Admin\SuperAdmin->index()';

    private static $completeMenu;
    private static $userMenu;

    public static function init() {
        self::$completeMenu = [ 'Run SQL Query' => __CLASS__ . '->askSqlQuery()',
            'Run Command' => __CLASS__ . '->askCommand()',
            'Update tblNextNumber' => __CLASS__ . '->updateNextNumber()',
            'Reload Preferences' => __CLASS__ . '->reloadPreferences()',
            'File Checksum' => __CLASS__ . '->fileChecksum()',
            'Init Timezones' => __CLASS__ . '->initTimeZone()',
            'Review Images' => '\Jackbooted\Admin\ImagePositionLocator->index()',
            'CRON Manager' => '\Jackbooted\Cron\CronManager->index()',
            'Schedule Manager' => '\Jackbooted\Cron\SchedulerManager->index()',
        ];
        self::$userMenu = [];
    }

    public static function getMenu() {
        if ( count( self::$userMenu ) == 0 ) {
            foreach ( self::$completeMenu as $title => $action ) {
                if ( Privileges::access( $action ) === true )
                    self::$userMenu[$title] = $action;
            }
        }
        return self::$userMenu;
    }

    public static function menu() {
        if ( Privileges::access( __METHOD__ ) !== true || !G::isLoggedIn() )
            return '';

        $resp = new Response ();
        $html = Tag::hTag( 'b' ) . 'Super Admin Menu' . Tag::_hTag( 'b' ) .
                Tag::ul( [ 'id' => 'menuList' ] );

        foreach ( self::getMenu() as $title => $action ) {
            $html .= Tag::li() .
                    Tag::hRef( '?' . $resp->action( $action )->toUrl(), $title ) .
                    Tag::_li();
        }

        $html .= Tag::_ul();

        return $html;
    }

    public function index() {
        if ( !G::isLoggedIn() ) {
            return Login::controller( Login::DEF );
        }
        else {
            return 'Select item from menu';
        }
    }

    protected function askSqlQuery() {
        $text = Request::get( 'SQLTEXT' );

        $html = '<b>Direct SQL</b><br/>' .
                Tag::form() .
                Response::factory()->action( __CLASS__ . '->runSqlQuery()' )->toHidden() .
                Tag::textArea( 'SQLTEXT', $text, [ 'rows' => 5, 'cols' => 40 ] ) . '<br/>' .
                Tag::submit( 'Go' ) .
                Tag::_form();
        return $html;
    }

    protected function runSqlQuery() {
        $sql = Request::get( 'SQLTEXT' );

        if ( !preg_match( '/^(SELECT|SHOW|DESCRIBE).*$/im', $sql ) ) {
            $html = '<br><b>SELECT SQL only</b>';
        }
        else {
            $html = DBTable::factory( DB::DEF, $sql, null, DB::FETCH_ASSOC )->__toString();
        }
        return $this->askSqlQuery() . '<br/>' . $html;
    }

    protected function askCommand() {
        $text = Request::get( 'CMDTEXT' );

        $html = '<b>Direct Command Access</b><br/>' .
                Tag::form() .
                Response::factory()->action( __CLASS__ . '->runCommand()' )->toHidden() .
                Tag::textArea( 'CMDTEXT', $text, [ 'rows' => 5, 'cols' => 40 ] ) . '<br/>' .
                Tag::submit( 'Go' ) .
                Tag::_form();
        return $html;
    }

    protected function runCommand() {
        $cmd = Request::get( 'CMDTEXT' );

        echo '<pre>';
        echo htmlspecialchars( system( $cmd, $return_var ) );
        echo '</pre>';
        return $this->askCommand() . '<br/>Returned Value: ' . $return_var;
    }

    protected function updateNextNumber() {
        $backMsg = '';

        $tableList = DBMaintenance::getTableList();
        foreach ( $tableList as $t ) {

            // Make sure that it is our table and not something else
            if ( preg_match( '/^tbl.*$/', $t ) )
                continue;

            if ( DBMaintenance::addTableToNextNumber( $t, 'XXX000000' ) )
                $backMsg .= '<br/>Added ' . $t;
        }

        if ( $backMsg == '' )
            $backMsg = '<br/>No Updates required';

        return '<b>Updated tblNextNumber</b>' .
                $backMsg;
    }

    protected function reloadPreferences() {
        Login::loadPreferences( G::get( 'fldUser' ) );
        return 'Reloaded Preferences';
    }

    public function initTimeZone() {
        DB::exec( DB::DEF, 'TRUNCATE tblTimeZone' );
        DB::exec( DB::DEF, "UPDATE tblNextNumber SET fldNext=1 WHERE fldTable='tblTimeZone'" );

        foreach ( timezone_abbreviations_list() as $abbr => $timezone ) {
            foreach ( $timezone as $val ) {
                if ( isset( $val['timezone_id'] ) ) {
                    DB::exec( DB::DEF, 'INSERT INTO tblTimeZone VALUES(?,?,?,?)', [ DBMaintenance::dbNextNumber( DB::DEF, 'tblTimeZone' ),
                        $val['timezone_id'],
                        'UTC ' . number_format( $val['offset'] / 60.0 / 60.0, 1 ) . ' hours',
                        number_format( $val['offset'] / 60.0 / 60.0, 1 ) ] );
                }
            }
        }
        return Grid::factory( 'SELECT * FROM tblTimeZone' )->index();
    }

    public function fileChecksum() {
        $messageArray = [];

        $dirList = PHPExt::dirSearch( Cfg::get( 'site_path' ), '/^[^_].*$/' );
        $len = strlen( Cfg::get( 'site_path' ) ) + 1;
        foreach ( $dirList as &$path )
            $path = substr( $path, $len );
        $tab = new DBTable( DB::DEF, 'SELECT * FROM tblFileCheck' );
        foreach ( $tab as $row ) {
            if ( in_array( $row['fldFileName'], $dirList ) ) {
                $fullPath = Cfg::get( 'site_path' ) . '/' . $row['fldFileName'];
                $fileSize = filesize( $fullPath );
                $sha1 = sha1_file( $fullPath );
                if ( $fileSize != $row['fldSize'] ) {
                    $messageArray[$row['fldFileName']] = 'Mismatch file size. was: ' . $row['fldSize'] . ' now: ' . $fileSize;
                }
                else if ( $sha1 != $row['fldCRC'] ) {
                    $messageArray[$row['fldFileName']] = 'Mismatch SHA1. was: ' . $row['fldCRC'] . ' now: ' . $sha1;
                }
            }
            else {
                $messageArray[$row['fldFileName']] = 'File deleted';
            }
        }

        $oldFileList = $tab->getColumn( 'fldFileName' );
        foreach ( $dirList as $fileName ) {
            if ( !in_array( $fileName, $oldFileList ) ) {
                $messageArray[$fileName] = 'New file';
            }
        }

        $html = '';
        if ( count( $messageArray ) != 0 ) {
            foreach ( $messageArray as $key => $val ) {
                $html .= $key . ': ' . $val . '<br/>';
            }
        }
        else {
            $html = 'No Changes<br/>';
        }

        $rebaseButton = Tag::linkButton( '?' . Response::factory()->action( __CLASS__ . '->' . __FUNCTION__ . 'Rebase()' ), 'Rebase' );
        return $rebaseButton . '<br/>' . $html . $rebaseButton;
    }

    public function fileChecksumRebase() {
        DB::exec( DB::DEF, 'TRUNCATE tblFileCheck' );

        $dirList = PHPExt::dirSearch( Cfg::get( 'site_path' ), '/^[^_].*$/' );
        $len = strlen( Cfg::get( 'site_path' ) ) + 1;
        $fileCount = 0;

        foreach ( $dirList as $fullPath ) {
            $fileCount ++;
            DB::exec( DB::DEF, 'INSERT INTO tblFileCheck VALUES(?,?,?,?)', [ DBMaintenance::dbNextNumber( DB::DEF, 'tblFileCheck' ),
                substr( $fullPath, $len ),
                filesize( $fullPath ),
                sha1_file( $fullPath ) ] );
        }
        return "Updated $fileCount files<br/>" .
                $this->fileChecksum();
    }

}
