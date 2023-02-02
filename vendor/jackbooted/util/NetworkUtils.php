<?php

namespace Jackbooted\Util;

/*
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 */

class NetworkUtils extends JB {

    public static function whatIsMyIP() {
        return file_get_contents( 'https://api.ipify.org' );
    }

    public static function ping( $host, $timeout = 1 ) {
        /* ICMP ping packet with a pre-calculated checksum */
        $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
        $ts = microtime( true );

        if ( ( $socket = @socket_create( AF_INET, SOCK_RAW, 1 ) ) === false ) {
            die( __METHOD__ . ' Can only be called if you are root' );
        }

        socket_set_option( $socket, SOL_SOCKET, SO_RCVTIMEO, [ 'sec' => $timeout, 'usec' => 0 ] );
        socket_connect( $socket, $host, null );
        socket_send( $socket, $package, strLen( $package ), 0 );
        if ( socket_read( $socket, 255 ) ) {
            $result = microtime( true ) - $ts;
        }
        else {
            $result = false;
        }
        socket_close( $socket );
        return $result;
    }

}
