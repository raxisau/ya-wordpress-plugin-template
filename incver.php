#!/usr/local/bin/php
<?php
if ( ( $sapi = php_sapi_name() ) != 'cli' ) {
    echo "Can only run on command line: {$sapi}\n";
    exit;
}

$fileName = ( isset( $_SERVER['argv'][1] ) ) ? $_SERVER['argv'][1] : 'ya-wordpress-plugin-template.php';

if ( ! file_exists( $fileName ) ) {
    echo "File does not exist: {$fileName}\n";
    exit;
}

$vers1Re = '/@version\s*([0-9.]+)/m';                      // @version       1.1.17
$vers2Re = '/\*\sVersion:\s*([0-9.]+)/m';                  // * Version:       1.1.17
$vers3Re = '/YAWPT_VERSION\',\s*\'([0-9.]+)/m';            // YAWPT_VERSION',     '1.1.17
$phpFileData = file_get_contents( $fileName );

if ( ! preg_match( $vers1Re, $phpFileData, $matches ) ) {
    echo "Cannot find version number\n";
    exit;
}

[ $major, $minor, $release ] = explode( '.', $matches[1], 3 );
$release ++;

$newVer1 = "@version       {$major}.{$minor}.{$release}";
$newVer2 = "* Version:       {$major}.{$minor}.{$release}";
$newVer3 = "YAWPT_VERSION',     '{$major}.{$minor}.{$release}";

$phpFileData = preg_replace( $vers1Re, $newVer1, $phpFileData );
$phpFileData = preg_replace( $vers2Re, $newVer2, $phpFileData );
$phpFileData = preg_replace( $vers3Re, $newVer3, $phpFileData );

file_put_contents( $fileName, $phpFileData );

passthru( 'git commit -am "Incremented version"; git push; ./zip.sh' );

