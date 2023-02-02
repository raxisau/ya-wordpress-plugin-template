<?php

namespace Jackbooted\Security;

use \Jackbooted\Config\Cfg;
use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\Html\WebPage;
use \Jackbooted\Security\Password;

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
class Captcha extends WebPage {

    private $value;
    private $hatch;

    public function __construct( $value = null, $hatch = 14 ) {
        parent::__construct();
        $this->value = ( $value == null ) ? Password::passGen( 6, Password::UPPER_ALPHA ) : $value;
        $this->hatch = $hatch;
    }

    public function imageUrl() {
        $url = Cfg::siteUrl() . '/ajax.php?' .
                        Response::factory()
                                ->action( __CLASS__ . '::img()' )
                                ->set( '_CP1', $this->value )
                                ->set( '_CP4', $this->hatch )
                                ->toUrl( Response::UNIQUE_CSRF );

        return $url;
    }

    public function getValue() {
        return $this->value;
    }

    public static function img() {
        header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
        header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
        header( 'Cache-Control: no-store, no-cache, must-revalidate' );
        header( 'Cache-Control: post-check=0, pre-check=0', false );
        header( 'Pragma: no-cache' );
        header( 'Content-type: image/jpeg' );

        $captchaValue = Request::get( '_CP1' );
        $hatch        = Request::get( '_CP4' );

        $fontAngle = 0.0;
        $fontSize = 16.0;
        $fontFile = dirname( __FILE__ ) . '/fonts/WAVY.TTF';

        $box = imagettfbbox( $fontSize, $fontAngle, $fontFile, $captchaValue );
        $min_x = min( [ $box[0], $box[2], $box[4], $box[6] ] );
        $max_x = max( [ $box[0], $box[2], $box[4], $box[6] ] );
        $min_y = min( [ $box[1], $box[3], $box[5], $box[7] ] );
        $max_y = max( [ $box[1], $box[3], $box[5], $box[7] ] );
        $w = ( $max_x - $min_x ) * 1.1;
        $h = ( $max_y - $min_y ) * 1.4;

        $im = imagecreatetruecolor( $w, $h ) or die( 'Cannot Initialize new GD image stream' );

        // Write the text
        imagettftext( $im, $fontSize, $fontAngle, 4, $h - 4, self::textColor( $im ), $fontFile, $captchaValue );

        // Hatch
        for ( $i = -$h; $i < $w; $i += $hatch ) {
            imageline( $im, $i, 0, $i + $h, $h, self::lineColor( $im ) );
            imageline( $im, $i, $h, $i + $h, 0, self::lineColor( $im ) );
        }

        // Output
        imagejpeg( $im );
        imagedestroy( $im );
        exit;
    }

    private static function lineColor( $im ) {
        $lo = 70;
        $hi = 255;
        return imagecolorallocate( $im, rand( $lo, $hi ), rand( $lo, $hi ), rand( $lo, $hi ) );
    }

    private static function textColor( $im ) {
        $lo = 210;
        $hi = 255;
        return imagecolorallocate( $im, rand( $lo, $hi ), rand( $lo, $hi ), rand( $lo, $hi ) );
    }

}
