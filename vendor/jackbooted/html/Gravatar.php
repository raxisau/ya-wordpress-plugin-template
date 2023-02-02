<?php

namespace Jackbooted\Html;

use \Jackbooted\Config\Cfg;

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
 * http://en.gravatar.com
 * mm: (mystery-man) a simple, cartoon-style silhouetted outline of a person (does not vary by email hash)
 * identicon: a geometric pattern based on an email hash
 * monsterid: a generated 'monster' with different colors, faces, etc
 * wavatar: generated faces with differing features and backgrounds
 * retro: awesome generated, 8-bit arcade-style pixelated faces
 */
class Gravatar extends \Jackbooted\Util\JB {

    private static $URL;

    const ICO = '%s/avatar/%s?s=%d&r=%s&d=%s';

    static $gravType = 'mm';

    public static function init() {
        self::$gravType = Cfg::get( 'gravatar', 'wavatar' );
        if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) {
            self::$URL = 'https://secure.gravatar.com';
        }
        else {
            self::$URL = 'http://www.gravatar.com';
        }
    }

    public static function icon( $email, $size = 24, $rating = 'PG', $type = null ) {
        if ( $type == null ) {
            $type = self::$gravType;
        }
        $gHash = md5( strtolower( trim( $email ) ) );

        $tPath = Cfg::get( 'tmp_path' );
        $fName = 'GRAV' . $size . $type . $gHash . '.png';
        $fPath = $tPath . '/' . $fName;

        // Locally Caches the gavatar image
        if ( !file_exists( $fPath ) ) {
            copy( sprintf( self::ICO, self::$URL, $gHash, $size, $rating, $type ), $fPath );
            if ( !file_exists( $fPath ) ) {
                return Tag::img( sprintf( self::ICO, self::$URL, $gHash, $size, $rating, $type ) );
            }
        }

        return Tag::img( Cfg::get( 'site_url' ) . '/' . basename( $tPath ) . '/' . $fName );
    }

    public static function getURL() {
        return self::$URL;
    }

}
