<?php

namespace Jackbooted\Html;

use \Jackbooted\Config\Cfg;

/** Javascript.php - methods for generating Javascript related html
 *
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 *
 */
class JS extends \Jackbooted\Util\JB {

    const JQUERY = 'jquery.min.js';
    const JQUERY_MOB = 'jquery.mobile.min.js';
    const JQUERY_UI = 'jquery-ui.min.js';
    const JQUERY_UI_DATETIME = 'jquery-ui-timepicker-addon.js';
    const JQUERY_UI_CSS = 'jquery-ui.min.css';
    const PHPLIVEX = 'phplivex.js';
    const FACEBOX = 'facebox.js';
    const JQUERY_TIPTIP = 'jquery.tipTip.minified.js';
    const JQUERY_BUBBLEPOPUP = 'jquery.bubblepopup.min.js';

    private static $JS_DEPEND = [
        self::JQUERY_UI          => [ self::JQUERY, self::JQUERY_UI_CSS, 'jquery-ui.custom.css' ],
        self::JQUERY_UI_DATETIME => [ self::JQUERY_UI, 'jquery-ui-timepicker-addon.css' ],
        self::FACEBOX            => [ 'facebox.css', self::JQUERY ],
        self::JQUERY_TIPTIP      => [ 'tipTip.css',  self::JQUERY ],
        self::JQUERY_BUBBLEPOPUP => [ 'jquery.bubblepopup.css', self::JQUERY ],
        self::JQUERY_MOB         => [ 'jquery.mobile.min.css', 'jquery-1.8.2.min.js' ],
    ];
    private static $LF = "\n";

    /**
     * Ensures that there are no frames for this window
     * @return string the javascrip[t to ensure that there is no frame
     */
    public static function noFrames() {
        return self::javaScript( 'if (parent.frames.length > 0) parent.location.href = self.document.location;' );
    }

    /**
     * Sets the linefeed on or off. During development it is good to have it on.
     * @param boolean $flag true means want line feeds
     */
    public static function setLineFeed( $flag ) {
        self::$LF = ( $flag ) ? "\n" : '';
    }

    /** Adds the HTML Script tags to the provided javascript string and returns
     * the resulting HTML
     * @param $str The javascript string
     * @returns var The resulting HTML
     * @public
     */
    static function javaScript( $str ) {
        return Tag::hTag( 'script', [ 'type' => 'text/javascript', 'language' => 'JavaScript' ] ) . self::$LF .
                $str . self::$LF .
                Tag::_hTag( 'script' ) . self::$LF;
    }

    /**
     * Creates CSS for the passed string
     * @param type $str the CSS data
     * @return type The resulting HTML
     */
    static function css( $str ) {
        return Tag::hTag( 'style', [ 'type' => 'text/css' ] ) . self::$LF .
                $str . self::$LF .
                Tag::_hTag( 'style' ) . self::$LF;
    }

    /**
     * Returns a javascript library to load up, and ensures that it is only loaded once
     * @param string $lib the library to load up
     * @param boolean force If set to true, will ignore previous included libraries
     * @return string html needed to include this javascript library
     */
    private static $displayedLibraries = [];

    static function already( $lib ) {
        self::$displayedLibraries[$lib] = true;
    }

    static function library( $lib, $force = false ) {
        if ( !$force && isset( self::$displayedLibraries[$lib] ) ) {
            return '';
        }
        self::$displayedLibraries[$lib] = true;

        if ( !preg_match( '/^http(s)?:\/\/.*$/i', $lib ) ) {
            $lib = Cfg::get( 'js_url' ) . '/' . $lib;
        }

        if ( preg_match( '/^.*\.js$/i', $lib ) || preg_match( '/^.*jsapi$/i', $lib ) ) {
            return Tag::hTag( 'script', [ 'type' => 'text/javascript', 'src' => $lib ] ) .
                    Tag::_hTag( 'script' ) . self::$LF;
        }
        else if ( preg_match( '/^.*\.css$/i', $lib ) ) {
            $attribs = [ 'type' => 'text/css', 'href' => $lib, 'rel' => 'stylesheet' ];
            if ( preg_match( '/^.*\.print\.css$/i', $lib ) ) {
                $attribs['media'] = 'print';
            }
            return Tag::hTag( 'link', $attribs ) . Tag::_hTag( 'link' ) . self::$LF;
        }
        else {
            return '';
        }
    }

    static function libraryWithDependancies( $lib ) {
        $html = '';
        if ( isset( self::$JS_DEPEND[$lib] ) ) {
            foreach ( self::$JS_DEPEND[$lib] as $dLib ) {
                $html .= self::libraryWithDependancies( $dLib );
            }
        }
        $html .= self::library( $lib );

        return $html;
    }

    /**
     * Encodes the provided Javascript string into ASCII
     * @param $str The javascript string
     * @returns var The Encoded Javascript
     * @public
     */
    public static function javaScriptEncode( $s ) {
        $str = '';
        $cnt = strlen( $s );
        for ( $i = 0; $i < $cnt; $i++ ) {
            if ( $i > 0 ) {
                $str .= ",";
            }
            $str .= ord( substr( $s, $i, $i + 1 ) );
        }
        return self::javascript( 'document.write ( String.fromCharCode ( ' . $str . ' ) );' );
    }

    /**
     * Creates and returns a Javascript block to display ( alert ) the
     * provided Error msg
     * @param $str The error message to display
     * @returns var The resulting HTML
     * @public
     */
    public static function showError( $str ) {
        return self::javascript( "alert ( '$str' )" );
    }

    /**
     *  Creates and returns a Javascript block to display ( alert ) the
     * provided Error msg and return to the previous page
     * @param $str The error message to display
     * @returns var The resulting HTML
     * @public
     */
    public static function showErrorBack( $str ) {
        return self::javascript( "alert ( '$str' ); window.history.back ( );" );
    }
}
