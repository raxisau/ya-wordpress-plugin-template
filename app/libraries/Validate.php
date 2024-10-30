<?php
namespace App\Libraries;

use \Jackbooted\Forms\Request;

class Validate extends \Jackbooted\Util\JB {
    const OUTPUT_RAW  = 'raw';
    const OUTPUT_HTML = 'raw';

    public static function  creditCard( $selectors=['inputCardNumber','inputCardExpiry','inputCardCVV'], $outputType=self::OUTPUT_HTML ) {
        $payPAN = preg_replace( '/[^\d]/', '', Request::get( $selectors[0] ) );
        $payExp = preg_replace( '/[^\d]/', '', Request::get( $selectors[1] ) );
        $payCVV = preg_replace( '/[^\d]/', '', Request::get( $selectors[2] ) );

        if ( ! in_array( strlen( $payPAN ), [ 15, 16 ] ) ) {
            $msg = ( $outputType == self::OUTPUT_HTML ) ? self::errMsg( \App\ErrMsg::PAN ) : \App\ErrMsg::PAN;
            return [ $payPAN, $payExp, $payCVV, $msg ];
        }
        if ( strlen( $payExp ) != 4 ) {
            $msg = ( $outputType == self::OUTPUT_HTML ) ? self::errMsg( \App\ErrMsg::EXP ) : \App\ErrMsg::EXP;
            return [ $payPAN, $payExp, $payCVV, $msg ];
        }
        if ( ! in_array( strlen( $payCVV ), [ 3, 4 ] ) )  {
            $msg = ( $outputType == self::OUTPUT_HTML ) ? self::errMsg( \App\ErrMsg::CVV ) : \App\ErrMsg::CVV;
            return [ $payPAN, $payExp, $payCVV, $msg ];
        }
        return [ $payPAN, $payExp, $payCVV, false ];
    }

    public static function email( $selector='inputEmail', $outputType=self::OUTPUT_HTML ) {
        $email = Request::get( $selector );
        if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            $errMsg = sprintf( \App\ErrMsg::EMAIL_FORMAT, $email );
            $msg = ( $outputType == self::OUTPUT_HTML ) ? self::errMsg( $errMsg ) : $errMsg;
            return [ $email, $msg ];
        }

        return [ $email, false ];
    }

    public static function phone( $countryCode='+61', $selector='inputPhone', $outputType=self::OUTPUT_HTML ) {
        // Normalise the phone number from the form input
        $origPhone = Request::get( $selector );
        if ( preg_match( '/^(\+[\d]+)\D(.*)$/m', $origPhone, $matches ) ) {
            $countryCode = $matches[1];
            $number      = preg_replace( '/[^\d]/', '', $matches[2] );
        }
        else {
            $number      = substr( preg_replace( '/[^\d]/', '', $origPhone ), -9 );
        }

        $phone = $countryCode . '.' . $number;
        if ( strlen( $phone ) < 10 )  {
            $errMsg = sprintf( \App\ErrMsg::PHONE, $origPhone );
            $msg = ( $outputType == self::OUTPUT_HTML ) ? self::errMsg( $errMsg ) : $errMsg;
            return [ $phone, $msg ];
        }

        return [ $phone, false ];
    }

    public static function name( $selector='inputFullName', $outputType=self::OUTPUT_HTML ) {
        // Split up the name fromthe form variable
        $names = explode( ' ', Request::get( $selector ), 2 );
        if ( count( $names ) < 2 ) {
            return [ $names[0], 'Missing', false ];
        }

        return [ $names[0], $names[1], false ];
    }

    public static function domain( $selector='inputDomainName', $outputType=self::OUTPUT_HTML ) {
        $domain = strtolower( trim( Request::get( $selector ) ) );

        if ( ! \App\App::isValidDomain( $domain ) ) {
            $errMsg = sprintf( \App\ErrMsg::DOMAIN_FORMAT, $domain );
            $msg = ( $outputType == self::OUTPUT_HTML ) ? self::errMsg( $errMsg ) : $errMsg;
            return [ $domain, $msg ];
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
