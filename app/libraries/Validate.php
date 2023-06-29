<?php
namespace App\Libraries;

use \Jackbooted\Forms\Request;

class Validate extends \Jackbooted\Util\JB {

    public static function  creditCard() {
        $payPAN = preg_replace( '/[^\d]/', '', Request::get( 'inputCardNumber' ) );
        $payExp = preg_replace( '/[^\d]/', '', Request::get( 'inputCardExpiry' ) );
        $payCVV = preg_replace( '/[^\d]/', '', Request::get( 'inputCardCVV' ) );

        if ( ! in_array( strlen( $payPAN ), [ 15, 16 ] ) ) {
            return [ $payPAN, $payExp, $payCVV, self::errMsg( \App\ErrMsg::PAN ) ];
        }
        if ( strlen( $payExp ) != 4 ) {
            return [ $payPAN, $payExp, $payCVV, self::errMsg( \App\ErrMsg::EXP ) ];
        }
        if ( ! in_array( strlen( $payCVV ), [ 3, 4 ] ) )  {
            return [ $payPAN, $payExp, $payCVV, self::errMsg( \App\ErrMsg::CVV ) ];
        }
        return [ $payPAN, $payExp, $payCVV, false ];
    }

    public static function email() {
        $email = Request::get( 'inputEmail' );
        if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            $errMsg = sprintf( \App\ErrMsg::EMAIL_FORMAT, $email );
            return [ $email, self::errMsg( $errMsg ) ];
        }

        return [ $email, false ];
    }

    public static function phone() {
        // Normalise the phone number from the form input
        $origPhone = Request::get( 'inputPhone' );
        $phone = '+61.' . substr( preg_replace( '/[^\d]/', '', $origPhone ), -9 );
        if ( strlen( $phone ) != 13 )  {
            $errMsg = sprintf( \App\ErrMsg::PHONE, $origPhone );
            return [ $phone, self::errMsg( $errMsg ) ];
        }

        return [ $phone, false ];
    }

    public static function name() {
        // Split up the name fromthe form variable
        list ( $firstName, $lastName ) = explode( ' ', Request::get( 'inputFullName' ), 2 );
        if ( $lastName == '' ) {
            return [ $firstName, $lastName, self::errMsg( \App\ErrMsg::LASTNAME ) ];
        }

        return [ $firstName, $lastName, false ];
    }

    public static function domain() {
        $domain = trim( Request::get( 'inputDomainName' ) );

        if ( ! preg_match('/^[a-z,0-9]+[a-z,0-9,\-,\_]*(\.[a-z]{2,3})?\.au$/', $domain ) ) {
            $errMsg = sprintf( \App\ErrMsg::DOMAIN_FORMAT, $domain );
            return [ $domain, self::errMsg( $errMsg ) ];
        }

        return [ $domain, false ];
    }

    private static function errMsg( $val, $title='Error' ) {
        $data = [
            'message' => $val,
            'title'   => $title
        ];
        $template = new \Jackbooted\Html\Template( dirname( dirname( __DIR__ ) ) . '/partials/message_error.html', \Jackbooted\Html\Template::FILE );
        return $template->replace( $data )->toHtml();
    }
}
