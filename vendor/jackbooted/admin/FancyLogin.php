<?php

namespace Jackbooted\Admin;

use \Jackbooted\Config\Cfg;
use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBMaintenance;
use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\G;
use \Jackbooted\Html\Gravatar;
use \Jackbooted\Html\JS;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\Validator;
use \Jackbooted\Html\WebPage;
use \Jackbooted\Mail\Mailer;
use \Jackbooted\Security\Captcha;
use \Jackbooted\Security\Privileges;
use \Jackbooted\Html\Widget;
use \Jackbooted\Security\Password;

/**
 * @copyright Confidential and copyright (c) 2024 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 */

/**
 */
class FancyLogin extends Login {

    const ACTION = '_LL_ACT';

    protected $loggedInMenuItems;

    public static function controller( $default = '', $actionKey = self::ACTION ) {
        return WebPage::controller( __CLASS__ . '->index()', self::ACTION );
    }

    public function __construct() {
        parent::__construct();
        $this->loggedInMenuItems = [ 'My Account' => '\Jackbooted\Admin\Admin->editAccount()' ];
    }

    public function index() {
        self::$log->trace( 'Entering ' . __METHOD__ );
        $html = ( G::isLoggedIn() ) ? $this->displayUserDetails() : $this->loginForm();
        self::$log->trace( 'Exiting ' . __METHOD__ );
        return $html;
    }

    private function displayUserDetails() {
        self::$log->trace( 'Entering ' . __METHOD__ );
        $jQuery = <<<JS
            jQuery().ready(function() {
                jQuery('#hoverimage').hover( function () { jQuery('#extralinks').fadeIn('fast'); },
                                        function () { setTimeout("jQuery('#extralinks').fadeOut('slow');", 3000 ); });
            });
        JS;

        $resp = new Response ();
        $html = JS::library( JS::JQUERY ) .
                JS::javaScript( $jQuery ) .
                Tag::table() .
                  Tag::tr() .
                    Tag::td( [ 'id' => 'hoverimage', 'class' => 'logindetails', 'nowrap' => 'nowrap' ] ) .
                      'Welcome ' . $this->getDisplayName() . Gravatar::icon( G::get( 'fldUser' ) ) .
                    Tag::_td() .
                  Tag::_tr() .
                  Tag::tr() .
                    Tag::td( [ 'id' => 'extralinks', 'style' => 'display: none;' ] ) .
                      Tag::ul( [ 'id' => 'vertMenu' ] ) .
                        Tag::li() .
                          Tag::hRef( 'ajax.php?' . $resp->action( '\Jackbooted\Admin\Login->logout()' )->toUrl(), 'Logout' ) .
                        Tag::_li();
        foreach ( $this->loggedInMenuItems as $name => $act ) {
            $html .=    Tag::li() .
                          Tag::hRef( '?' . $resp->action( $act )->toUrl(), $name ) .
                        Tag::_li();
        }

        $html .=      Tag::_ul() .
                    Tag::_td() .
                  Tag::_tr() .
                Tag::_table();

        self::$log->trace( 'Exiting ' . __METHOD__ );
        return $html;
    }

    private function loginForm() {
        self::$log->trace( 'Entering ' . __METHOD__ );
        $jsUrl = Cfg::get( 'js_url' );
        $jQuery = <<<JS
            jQuery().ready(function() {
                jQuery('#hoverimage').hover ( function () { jQuery('#extralinks').fadeIn('fast'); },
                                         function () { setTimeout("jQuery('#extralinks').fadeOut();", 3000 ); });
                jQuery('a.facebox').facebox({closeImage:   '$jsUrl/images/closelabel.png',
                                        loadingImage: '$jsUrl/images/loading.gif'

                });
            });
        JS;
        $formName = 'FancyLogin_loginForm';
        $valid = Validator::factory( $formName, 'LF' )
                ->addExists( self::LOGIN_FNAME, 'Email field must not be empty' )
                ->addExists( self::PASSW_FNAME, 'Password field must not be empty' );

        $resp = new Response ();
        $html = JS::library( JS::JQUERY ) .
                JS::libraryWithDependancies( JS::FACEBOX ) .
                JS::javaScript( $jQuery ) .
                Tag::table() .
                  Tag::tr() .
                    Tag::td( [ 'id' => 'hoverimage', 'class' => 'login', 'nowrap' => 'nowrap' ] ) .
                      $valid->toHtml() .
                      Tag::form( [ 'id' => $formName, 'name' => $formName, 'onSubmit' => $valid->onSubmit() ] ) .
                        $resp->set( self::ACTION, __CLASS__ . '->checkLogin()' )->toHidden() .
                        Tag::table() .
                          Tag::tr() .
                            Tag::td() . 'Email' . Tag::_td() .
                            Tag::td() .
                              Tag::text( self::LOGIN_FNAME, [ 'size' => 10, 'style' => 'opacity:0.5;filter:alpha(opacity=50)' ] ) .
                            Tag::_td() .
                            Tag::td() . 'Password:' . Tag::_td() .
                            Tag::td() .
                              Tag::password( self::PASSW_FNAME, [ 'size' => 10, 'style' => 'opacity:0.5;filter:alpha(opacity=50)' ] ) .
                            Tag::_td() .
                            Tag::td() . Tag::submit( 'Go' ) . Tag::_td() .
                          Tag::_tr() .
                        Tag::_table() .
                      Tag::_form() .
                    Tag::_td() .
                  Tag::_tr() .
                  Tag::tr() .
                    Tag::td( [ 'id' => 'extralinks', 'style' => 'display: none;' ] ) .
                      Tag::ul( [ 'id' => 'vertMenu' ] );

        if ( Cfg::get( 'jb_self_register', false ) ) {
            $html .=    Tag::li() .
                          Tag::hRef( 'ajax.php?' . $resp->action( __CLASS__ . '->newRegistration()' )->toUrl(), 'Register New Account', [ 'class' => 'facebox' ] ) .
                        Tag::_li();
        }

        $html .=        Tag::li() .
                          Tag::hRef( 'ajax.php?' . $resp->action( __CLASS__ . '->forgotPassword()' )->toUrl(), 'Forgot My Password', [ 'class' => 'facebox' ] ) .
                        Tag::_li() .
                      Tag::_ul() .
                    Tag::_td() .
                  Tag::_tr() .
                Tag::_table();

        self::$log->trace( 'Exiting ' . __METHOD__ );
        return $html;
    }

    public function newRegistration() {
        // Initialise the $msg and $action and $disclaimer variables
        $disclaimer = Cfg::get( 'disclaimer' );
        $formName = 'FancyLogin_newRegistration';

        $valid = Validator::factory( $formName, 'NR' )
                ->addExists( 'fldEmail', 'Email field is empty. Please insert valid email and resubmit' )
                ->addEmail( 'fldEmail', 'Email needs to exist and be correct format' )
                ->addExists( 'fldFirstName', 'First Name must exist' )
                ->addExists( 'fldCaptcha', 'You must enter Captcha Code' )
                ->addExists( 'fldLastName', 'Last Name must exist' );

        $cap = new Captcha ();

        $html = $valid->toHtml() .
                Tag::form( [ 'id' => $formName, 'name' => $formName, 'onSubmit' => $valid->onSubmit() ] ) .
                        Response::factory()
                        ->set( self::ACTION, __CLASS__ . '->signUp()' )
                        ->set( '_CAP', $cap->getValue() )
                        ->toHidden() .
                  Tag::table( [ 'align' => 'center', 'border' => 0, 'cellspacing' => 0, 'cellpadding' => 2 ] ) .
                    Tag::tr() .
                      Tag::td() . 'Email:' . Tag::_td() .
                      Tag::td() . Tag::text( 'fldEmail', Request::get( 'fldEmail' ) ) . Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td() . 'First&nbsp;Name:' . Tag::_td() .
                      Tag::td() . Tag::text( 'fldFirstName', Request::get( 'fldFirstName' ) ) . Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td() . 'Last&nbsp;Name:' . Tag::_td() .
                      Tag::td() . Tag::text( 'fldLastName', Request::get( 'fldLastName' ) ) . Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td() . Tag::img( $cap->imageUrl() ) . Tag::_td() .
                      Tag::td() . Tag::text( 'fldCaptcha' ) . Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td( [ 'colspan' => 2, 'nowrap' => 'nowrap', 'valign' => 'top' ] ) .
                        'Please Read Disclaimer:' .
                      Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td( [ 'colspan' => 2 ] ) .
                        Tag::hTag( 'iframe', [ 'src' => $disclaimer, 'width' => '100%' ] ) .
                        Tag::_hTag( 'iframe' ) .
                      Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td( [ 'colspan' => 2, 'align' => 'center' ] ) .
                        'By clicking below, you are stating that you understand and agree to the Disclamer above' .
                      Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td( [ 'colspan' => 2, 'align' => 'center' ] ) .
                        Tag::submit( 'New Registration' ) .
                      Tag::_td() .
                    Tag::_tr() .
                  Tag::_table() .
                Tag::_form();

        return $html;
    }

    public function forgotPassword() {
        // Initialise the $msg and $action and $disclaimer variables
        $formName = 'FancyLogin_forgotPassword';

        $valid = Validator::factory( $formName, 'FP' )
                ->addExists( 'fldEmail', 'Email field is empty. Please insert valid email and resubmit' )
                ->addEmail( 'fldEmail', 'Email is in valid format. Must be of the form a@b.com' );

        $html = $valid->toHtml() .
                Tag::form( [ 'id' => $formName, 'name' => $formName, 'onSubmit' => $valid->onSubmit() ] ) .
                  Response::factory()->set( self::ACTION, __CLASS__ . '->sendPW()' )->toHidden() .
                  Tag::table( [ 'align' => 'center', 'border' => 0, 'cellspacing' => 0, 'cellpadding' => 2 ] ) .
                    Tag::tr() .
                      Tag::td() . 'Email' . Tag::_td() .
                      Tag::td() . Tag::text( 'fldEmail' ) . Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td( [ 'colspan' => 2, 'align' => 'center' ] ) .
                        'Your Password will be reset and sent to you via email you have provided' .
                      Tag::_td() .
                    Tag::_tr() .
                    Tag::tr() .
                      Tag::td( [ 'colspan' => 2, 'align' => 'center' ] ) .
                        Tag::submit( 'Send Password' ) .
                      Tag::_td() .
                    Tag::_tr() .
                  Tag::_table() .
                Tag::_form();

        return $html;
    }

    public function signUp() {

        $checkIdSql = 'SELECT COUNT(*) FROM tblUser WHERE fldUser=?';

        if ( Request::get( '_CAP' ) != Request::get( 'fldCaptcha' ) ) {
            $msg = 'Invalid Security Code ' . $this->newRegistration();
        }
        else if ( DB::oneValue( DB::DEF, $checkIdSql, Request::get( 'fldEmail' ) ) != 0 ) {
            $msg = 'A user with email: ' . Request::get( 'fldEmail' ) . ' currently exists on this system<br/>' .
                    'Either choose a new email address or request a new password.' .
                    $this->newRegistration();
        }
        else {
            // Generate a password for the user
            $pw = Password::passGen( 10, Password::MEDIUM );

            // Add the User to the Database
            $now = time();
            if ( DB::driver() == DB::MYSQL ) {
                $sql = <<<SQL
                    INSERT INTO tblUser
                           (fldUserID,fldUser,fldFirstName,fldLastName,fldPassword,fldDomain,fldCreated,      fldLevel)
                    VALUES ( ?,       ?,      ?,           ?,          PASSWORD(?),?,        $now,            ? )
                SQL;
            }
            else {
                $sql = <<<SQL
                    INSERT INTO tblUser
                           (fldUserID,fldUser,fldFirstName,fldLastName,fldPassword,fldDomain,fldCreated,      fldLevel)
                    VALUES ( ?,       ?,      ?,           ?,          ?,          ?,        $now,            ? )
                SQL;
                $pw = hash( 'md5', $pw );
            }
            $params = [ DBMaintenance::dbNextNumber( DB::DEF, 'tblUser' ),
                Request::get( 'fldEmail' ),
                Request::get( 'fldFirstName' ),
                Request::get( 'fldLastName' ),
                $pw,
                Cfg::get( 'server' ),
                Privileges::getSecurityLevel( 'USER' ) ];
            DB::exec( DB::DEF, $sql, $params );

            $boss = Cfg::get( 'boss' );
            $desc = Cfg::get( 'desc' );

            $body = '<h3>New User: <b>%s %s</b><br>Email: <b>%s</b></h3><br>Has joined %s';

            // create the email message to notify about a new user
            Mailer::envelope()->format( Mailer::HTML_TEXT )
                    ->from( Request::get( 'fldEmail' ) )
                    ->to( $boss )
                    ->subject( 'New user has joined ' . $desc )
                    ->body( sprintf( $body, Request::get( 'fldFirstName' ), Request::get( 'fldLastName' ), Request::get( 'fldEmail' ), $desc ) )
                    ->send();

            $body = <<<TXT
                Thanks for signing up for %s

                Here are your login details

                Username: %s
                Password: %s

                Regards
                %s
            TXT;
            // create the email message to notify the new user of his/her login details
            Mailer::envelope()->from( $boss )
                    ->to( Request::get( 'fldEmail' ) )
                    ->subject( 'Welcome to ' . $desc )
                    ->body( sprintf( $body, $desc, Request::get( 'fldEmail' ), $pw, $desc ) )
                    ->send();

            // Let the user know that the registration was succesful
            $msg = 'Congratulations you have been signed up for ' . $desc . '<br>' .
                    'Soon you will receive a confirmation email that will contain' .
                    'your login details.';
        }

        return Widget::popupWrapper( $msg, -1 );
    }

    public function sendPW() {
        $sql = 'SELECT fldUserID FROM tblUser WHERE fldUser=?';

        if ( ( $id = DB::oneValue( DB::DEF, $sql, Request::get( 'fldEmail' ) ) ) === false ) {
            $msg = 'This email does not exist on this system.<br>' .
                    'Either choose a new email address or register as new customer.' .
                    $this->forgotPassword();
        }
        else {
            $pw = Password::passGen( 10, Password::MEDIUM );

            if ( DB::driver() == DB::MYSQL ) {
                $sql = 'UPDATE tblUser SET fldPassword=PASSWORD(?) WHERE fldUserID=?';
                DB::exec( DB::DEF, $sql, [ $pw, $id ] );
            }
            else {
                $sql = 'UPDATE tblUser SET fldPassword=? WHERE fldUserID=?';
                DB::exec( DB::DEF, $sql, [ hash( 'md5', $pw ), $id ] );
            }
            // Update the Database with the new Password combo

            $boss = Cfg::get( 'boss' );
            $desc = Cfg::get( 'desc' );

            // create the email message to notify about a password request
            $body = '<h3>User requested password<br>Email: <b>%s</b></h3><br>From %s';
            Mailer::envelope()->format( Mailer::HTML_TEXT )
                    ->from( Request::get( 'fldEmail' ) )
                    ->to( $boss )
                    ->subject( 'User requested password' )
                    ->body( sprintf( $body, Request::get( 'fldEmail' ), $desc ) )
                    ->send();

            $body = <<<TXT
                Message from %s

                Here are your login details

                Password: %s

                Regards
                %s
            TXT;
            // create the email message to notify the user of his/her login details
            Mailer::envelope()->from( $boss )
                    ->to( Request::get( 'fldEmail' ) )
                    ->subject( 'Login Request ' . $desc )
                    ->body( sprintf( $body, $desc, $pw, $desc ) )
                    ->send();

            $msg = 'Soon you will receive an email that will contain your login details.';
        }

        return Widget::popupWrapper( $msg, -1 );
    }

}
