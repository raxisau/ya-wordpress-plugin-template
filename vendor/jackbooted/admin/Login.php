<?php

namespace Jackbooted\Admin;

use \Jackbooted\Config\Cfg;
use \Jackbooted\Config\PreferenceLoader;
use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBMaintenance;
use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\G;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\Validator;
use \Jackbooted\Html\WebPage;
use \Jackbooted\Security\Privileges;
use \Jackbooted\Util\Cookie;
use \Jackbooted\Html\Widget;
use \Jackbooted\Security\Password;
use \Jackbooted\Mail\Mailer;

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
class Login extends WebPage {

    const LOGIN_NAME    = 'RW_4e832b50f61c5e87ec9e7264e6466e1f';
    const PASSWORD_NAME = 'RW_99cb4fc2271bcda4c9284aa7b2d8e262';
    const SESSHASH_NAME = 'RW_9289b6efa48c4e12633da5057107dfcb';
    const LOGIN_FNAME   = 'fldLoginID';
    const PASSW_FNAME   = 'fldPassword';
    const DEF           = '\Jackbooted\Admin\Login->index()';

    private   static $completeMenu;
    private   static $userMenu = null;

    public static function init() {
        self::$completeMenu = [
            'Logout' => [
                'action' => __CLASS__ . '::logOut()',
                'url' => 'ajax.php?'
            ],
            Cfg::get( 'desc' ) => [
                'action' => __CLASS__ . '::home()',
                'url' => '?'
            ]
        ];
    }

    public static function getMenu() {
        if ( self::$userMenu != null ) {
            return self::$userMenu;
        }

        self::$userMenu = [];
        foreach ( self::$completeMenu as $title => $action ) {
            if ( G::isLoggedIn() ) {
                self::$userMenu[$title] = $action;
            }
        }
        return self::$userMenu;
    }

    public static function menu() {
        if ( Privileges::access( __METHOD__ ) !== true || !G::isLoggedIn() ) {
            return '';
        }
        if ( count( self::getMenu() ) <= 0 ) {
            return '';
        }

        $resp = new Response ();
        $html = Tag::b() . 'Login Menu' . Tag::_b() .
                Tag::ul( [ 'id' => 'menuList' ] );

        foreach ( self::getMenu() as $title => $action ) {
            $html .= Tag::li() .
                       Tag::hRef( $action['url'] . $resp->action( $action['action'] )->toUrl(), $title ) .
                     Tag::_li();
        }

        $html .= Tag::_ul();

        return $html;
    }

    public static function sendLoginCookie( $username, $password ) {
        self::$log->trace( 'Entering ' . __METHOD__ );
        $hash = self::calculateHash( $username, $password );

        if ( Cfg::get( 'save_cookies', false ) ) {
            Cookie::set( self::LOGIN_NAME, $username );
            Cookie::set( self::PASSWORD_NAME, $password );
            Cookie::set( self::SESSHASH_NAME, $hash );
        }

        G::set( self::LOGIN_NAME, $username );
        G::set( self::PASSWORD_NAME, $password );
        G::set( self::SESSHASH_NAME, $hash );
        self::$log->trace( 'Exiting ' . __METHOD__ );
    }

    public static function getLoginCookie() {
        self::$log->trace( 'Entering ' . __METHOD__ );
        $username = G::get( self::LOGIN_NAME, '' );
        $password = G::get( self::PASSWORD_NAME, '' );
        $hash     = G::get( self::SESSHASH_NAME, '' );

        if ( $username == '' || $password == '' || $hash == '' ) {
            $username = Cookie::get( self::LOGIN_NAME, '' );
            $password = Cookie::get( self::PASSWORD_NAME, '' );
            $hash = Cookie::get( self::SESSHASH_NAME, '' );
        }
        self::$log->trace( 'Exiting ' . __METHOD__ );
        return [ $username, $password, $hash ];
    }

    public static function loadPreferencesFromCookies() {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        list ( $username, $password, $hash ) = self::getLoginCookie();
        if ( !self::checkAuthenticated( $username, $password, $hash ) ) {
            self::$log->trace( 'Exiting ' . __METHOD__ );
            return false;
        }

        if ( !isset( $_SESSION[G::SESS][G::PREFS] ) || ! is_object( $_SESSION[G::SESS][G::PREFS] ) ) {
            self::loadPreferences( $username );
            self::sendLoginCookie( $username, $password );
        }

        self::$log->trace( 'Exiting ' . __METHOD__ );
        return true;
    }

    public static function calculateHash( $username, $password ) {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        $hashArray = [ $username, $password, $_SERVER['REMOTE_ADDR'], time() ];
        self::$log->trace( 'Exiting ' . __METHOD__ );
        return serialize( $hashArray );
    }

    public static function testHash( $username, $password, $hash ) {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        if ( ( $hashArray = unserialize( $hash ) ) === false ) {
            self::$log->trace( 'Exiting ' . __METHOD__ );
            return false;
        }

        if ( $hashArray[0] != $username ) {
            self::$log->trace( 'Exiting ' . __METHOD__ );
            return self::$log->error( 'Incorrect username' );
        }

        if ( $hashArray[1] != $password ) {
            self::$log->trace( 'Exiting ' . __METHOD__ );
            return self::$log->error( 'Incorrect password' );
        }

        // Sorry to comment out code in production, but this is getting tiresome as
        // my internet provider is changing IPs every 5 minutes
        // This check will force a user to login again if they change location
        // else if ( $hashArray[2] != $_SERVER['REMOTE_ADDR'] )
        //    return self::$log->trace( 'Login from different IP' );
        if ( time() - $hashArray[3] > Cfg::get( 'session_timeout', 604800 ) ) {
            self::$log->trace( 'Exiting ' . __METHOD__ );
            return self::$log->error( 'Session timeout' );
        }

        self::$log->trace( 'Exiting ' . __METHOD__ );
        return true;
    }

    public static function checkAuthenticated( $username, $password, $hash = null ) {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        if ( ! isset( $username ) || $username == false || $username == '' ||
             ! isset( $password ) || $password == false || $password == '' ) {
            self::$log->trace( 'Exiting ' . __METHOD__ );
            return false;
        }

        $sqlLoginAttempts = <<<SQL
            SELECT COUNT(*)
            FROM   tblLoginAttempt
            WHERE  fldUsername=?
SQL;
        if ( $hash != null && !self::testHash( $username, $password, $hash ) ) {
            $sucessfulLogin = false;
        }
        else if ( DB::oneValue( DB::DEF, $sqlLoginAttempts, [ $username ] ) > 4 ) {
            $sucessfulLogin = false;
        }
        else {
            if ( DB::driver() == DB::MYSQL ) {
                $sql = <<<SQL
                    SELECT COUNT(*)
                    FROM   tblUser
                    WHERE  fldPassword=PASSWORD(?)
                    AND    fldUser=?
                    AND    fldFails<4
SQL;
                $numEntries = DB::oneValue( DB::DEF, $sql, [ $password, $username ] );
            }
            else {
                $sql = <<<SQL
                    SELECT COUNT(*)
                    FROM   tblUser
                    WHERE  fldPassword=?
                    AND    fldUser=?
                    AND    fldFails<4
SQL;
                $numEntries = DB::oneValue( DB::DEF, $sql, [ hash( 'md5', $password ), $username ] );
            }
            $sucessfulLogin = ( $numEntries == 1 );
        }

        if ( $sucessfulLogin ) {
            self::updateLastLogin( $username );
        }
        else {
            self::incrementFails( $username, $password );
        }

        self::$log->trace( 'Exiting ' . __METHOD__ );
        return $sucessfulLogin;
    }

    public static function updateLastLogin( $username ) {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        DB::exec( DB::DEF, 'UPDATE tblUser SET fldLastLogin=?,fldFails=0 WHERE fldUser=?', [ time(), $username ] );
        DB::exec( DB::DEF, 'DELETE FROM tblLoginAttempt WHERE fldUsername=?', [ $username ] );
        self::$log->trace( 'Exiting ' . __METHOD__ );
    }

    public static function incrementFails( $username, $password ) {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        $params = [ DBMaintenance::dbNextNumber( DB::DEF, 'tblLoginAttempt' ),
                    $username,
                    $password,
                    $_SERVER['HTTP_USER_AGENT'],
                    $_SERVER['REMOTE_ADDR'] ];
        DB::exec( DB::DEF, 'INSERT INTO tblLoginAttempt VALUES(?,?,?,?,?)', $params );
        DB::exec( DB::DEF, 'UPDATE tblUser SET fldFails=fldFails+1 WHERE fldUser=?', $username );
        self::$log->trace( 'Exiting ' . __METHOD__ );
        return DB::oneValue( DB::DEF, 'SELECT fldFails FROM tblUser WHERE fldUser=?', $username );
    }

    public static function clearFails() {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        $la = DB::exec( DB::DEF, 'DELETE FROM tblLoginAttempt WHERE fldUsername IN (SELECT fldUser FROM tblUser)' );
        $up = DB::exec( DB::DEF, 'UPDATE tblUser SET fldFails=0' );
        self::$log->trace( 'Exiting ' . __METHOD__ );
        return [ 0, "Cleared: $up Login Attempts: $la" ];
    }

    public static function loadPreferences( $user ) {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        $prefLoader = new PreferenceLoader( null, $user );
        $_SESSION[G::SESS][G::PREFS] = $prefLoader->getPreferences();
        G::setLoggedIn( true );
        self::$log->trace( 'Exiting ' . __METHOD__ );
    }

    public static function logOut() {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        self::killSession();
        self::$log->trace( 'Exiting ' . __METHOD__ );
        self::doRedirect();
    }

    public static function killSession() {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        Cookie::clear( self::LOGIN_NAME );
        Cookie::clear( self::PASSWORD_NAME );
        Cookie::clear( self::SESSHASH_NAME );

        $_SESSION[G::SESS][G::PREFS] = null;
        $_SESSION[G::SESS] = null;

        unset( $_SESSION[G::SESS][G::PREFS] );
        unset( $_SESSION[G::SESS] );

        session_unset();
        session_destroy();
        self::$log->trace( 'Exiting ' . __METHOD__ );
    }

    public static function home() {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        Request::set( WebPage::SAVE_URL, Cfg::siteUrl() );
        self::$log->trace( 'Exiting ' . __METHOD__ );
        self::doRedirect();
    }

    public static function doRedirect() {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        $redirectTime = 0;

        if ( ( $index = Cfg::get( 'index' ) ) == '' ) {
            $index = Cfg::siteUrl() . '/index.php';
        }

        $url = Request::get( WebPage::SAVE_URL, $index );

        echo ( sprintf( '<meta HTTP-EQUIV="REFRESH" content="%s; url=%s">', $redirectTime, $url ) );
        self::$log->trace( 'Exiting ' . __METHOD__ );
        exit;
    }

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        $formName = 'Login_index';
        $valid = Validator::factory( $formName )
                ->addExists( self::LOGIN_FNAME, 'Email field must not be empty' )
                ->addExists( self::PASSW_FNAME, 'Password field must not be empty' );

        $mobileAttribs = [];
        if ( G::isSmartPhone() ) {
            $mobileAttribs['type'] = 'email';
        }

        $resp = new Response ();
        $html = '<h2>Login</h2>' .
                $valid->toHtml() .
                Tag::form( [ 'action' => 'ajax.php', 'name' => $formName, 'onSubmit' => $valid->onSubmit() ] ) .
                  $resp->action( __CLASS__ . '->checkLogin()' )->toHidden() .
                  Tag::table() .
                    Tag::tr() .
                      Tag::td() . 'Email' . Tag::_td() .
                      Tag::td() .
                        Tag::text( self::LOGIN_FNAME, $mobileAttribs ) .
                      Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td() . 'Password:' . Tag::_td() .
                      Tag::td() .
                        Tag::password( self::PASSW_FNAME ) .
                      Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td([ 'colspan' => 3, 'aligh' => 'right' ]) .
                        Tag::submit( 'Login' ) .
                      Tag::_td() .
                    Tag::_tr() .
                  Tag::_table() .
                Tag::_form() .
                Tag::linkButton( '?' . $resp->action( __CLASS__ . '->forgotPassword()' )->toUrl(), 'Forgot Password' );

        self::$log->trace( 'Exiting ' . __METHOD__ );
        return $html;
    }

    public function forgotPassword() {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        // Initialise the $msg and $action and $disclaimer variables
        $formName = 'Login_forgotPassword';

        $valid = Validator::factory( $formName, 'FP' )
                ->addExists( 'fldEmail', 'Email field is empty. Please insert valid email and resubmit' )
                ->addEmail( 'fldEmail', 'Email is in valid format. Must be of the form a@b.com' );

        $html = '<h2>Password Reset</h2>' .
                $valid->toHtml() .
                Tag::form( [ 'id' => $formName, 'name' => $formName, 'onSubmit' => $valid->onSubmit() ] ) .
                  Response::factory()->action( __CLASS__ . '->sendPW()' )->toHidden() .
                  Tag::table() .
                    Tag::tr() .
                      Tag::td() . 'Email' . Tag::_td() .
                      Tag::td() .
                        Tag::text( 'fldEmail', [ 'title' => 'Your Password will be reset and sent to you via email you have provided' ] ) .
                      Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td( [ 'align' => 'right' ] ) .
                        Tag::submit( 'Reset PW' ) .
                      Tag::_td() .
                    Tag::_tr() .
                  Tag::_table() .
                Tag::_form() .
                Tag::linkButton( '?' . Response::factory()->action( __CLASS__ . '->index()' )->toUrl(), 'Back to Login' );

        self::$log->trace( 'Exiting ' . __METHOD__ );
        return $html;
    }

    public function sendPW() {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        $sql = 'SELECT fldUserID FROM tblUser WHERE fldUser=?';

        if ( ( $id = DB::oneValue( DB::DEF, $sql, Request::get( 'fldEmail' ) ) ) === false ) {
            self::$log->trace( 'Exiting ' . __METHOD__ );
            return $this->forgotPassword() .
                    Widget::popupWrapper( 'This email does not exist on this system.', -1 );
        }

        $pw = Password::passGen( 10, Password::MEDIUM );

        if ( DB::driver() == DB::MYSQL ) {
            $sql = 'UPDATE tblUser SET fldPassword=PASSWORD(?),fldFails=0 WHERE fldUserID=?';
            DB::exec( DB::DEF, $sql, [ $pw, $id ] );
        }
        else {
            $sql = 'UPDATE tblUser SET fldPassword=?,fldFails=0 WHERE fldUserID=?';
            DB::exec( DB::DEF, $sql, [ hash( 'md5', $pw ), $id ] );
        }
        // Update the Database with the new Password combo

        $boss = Cfg::get( 'boss' );
        $desc = Cfg::get( 'desc' );

        // create the email message to notify about a password request
        $body = '<h3>User requested password<br>Email: <b>%s</b></h3><br>From %s';
        Mailer::envelope()
                ->format( Mailer::HTML_TEXT )
                ->from( Request::get( 'fldEmail' ) )
                ->to( $boss )
                ->subject( 'User requested password' )
                ->body( sprintf( $body, Request::get( 'fldEmail' ), $desc ) )
                ->send();

        // create the email message to notify the user of his/her login details
        $body = "Message from %s\r\n\r\nHere is your new password\r\n\r\nPassword: %s\r\n\r\nRegards\r\n%s\r\n";
        Mailer::envelope()
                ->from( $boss )
                ->to( Request::get( 'fldEmail' ) )
                ->subject( 'Login Request ' . $desc )
                ->body( sprintf( $body, $desc, $pw, $desc ) )
                ->send();

        $msg = 'Soon you will receive an email that will contain your login details.';

        self::$log->trace( 'Exiting ' . __METHOD__ );
        return $this->index() .
                Widget::popupWrapper( $msg, -1 );
    }

    public function checkLogin() {
        self::$log->trace( 'Entering: ' . __METHOD__ );
        $username = Request::get( self::LOGIN_FNAME );
        $password = Request::get( self::PASSW_FNAME );

        if ( !isset( $username ) || $username == false || !isset( $password ) || $password == false ) {
            self::$log->trace( 'Exiting ' . __METHOD__ );
            return false;
        }

        if ( self::checkAuthenticated( $username, $password ) ) {
            self::$log->debug( 'Killing old session id: ' . session_id() );
            @session_regenerate_id( true );
            self::$log->debug( 'New session has taken over id: ' . session_id() );
            self::loadPreferences( $username );
            self::sendLoginCookie( $username, $password );
            self::$log->trace( 'Exiting ' . __METHOD__ );
            self::doRedirect();
        }
        else {
            self::$log->trace( 'Exiting ' . __METHOD__ );
            return 'Invalid Login Details' . $this->index();
        }
    }

    protected function getDisplayName() {
        self::$log->trace( 'Entering: ' . __METHOD__ );

        $name = G::get( 'fldFirstName' ) . ' ' . G::get( 'fldLastName' );
        if ( G::isLoggedIn() && G::accessLevel( Privileges::getSecurityLevel( 'SITE ADMIN' ) ) ) {
            $uName = Tag::hRef( 'superadmin.php', $name, [ 'class' => 'admin' ] );
        }
        else {
            $uName = Tag::e( $name );
        }
        self::$log->trace( 'Exiting ' . __METHOD__ );
        return $uName;
    }
}
