<?php

namespace Jackbooted\Util;

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
 * PHP Extensions utility class
 */
class PHPExt extends \Jackbooted\Util\JB {

    public static function is_assoc( $array ) {
        if ( !is_array( $array ) ) {
            return false;
        }

        foreach ( array_keys( $array ) as $k => $v ) {
            if ( $k !== $v ) {
                return true;
            }
        }

        return false;
    }

    public static function getTempDir() {
        if ( preg_match( '/^(RADWEB|JACKBOOTWEB).*$/', Cfg::get( 'version' ) ) ) {
            $tmpDir = Cfg::get( 'tmp_path' );
        }
        else {
            $tmpDir = '/tmp';

            if ( function_exists( 'sys_get_temp_dir' ) ) {
                $tmpDir = sys_get_temp_dir();
            }
            else {
                foreach ( [ 'TMP', 'TEMP', 'TMPDIR' ] as $envVar ) {
                    if ( ( $temp = getenv( $envVar ) ) !== false ) {
                        $tmpDir = $temp;
                        break;
                    }
                }
            }
        }

        // ensure that there is no trailing slash (Standard)
        $lastChar = substr( $tmpDir, -1 );
        if ( $lastChar == '/' || $lastChar == '\\' ) {
            $tmpDir = substr( $tmpDir, 0, -1 );
        }
        return $tmpDir;
    }

    public static function dirSearch( $dir, $matchesExp = null ) {
        $handle = opendir( $dir );
        $fileList = [];
        while ( false !== ( $file = readdir( $handle ) ) ) {
            if ( strpos( $file, '.' ) === 0 ) {
                continue;
            }
            if ( $matchesExp != null && !preg_match( $matchesExp, $file ) ) {
                continue;
            }

            $fullPathName = $dir . '/' . $file;
            if ( is_dir( $fullPathName ) ) {
                $fileList = array_merge( self::dirSearch( $fullPathName ), $fileList );
            }
            else {
                $fileList[] = $fullPathName;
            }
        }
        closedir( $handle );
        return $fileList;
    }

}
