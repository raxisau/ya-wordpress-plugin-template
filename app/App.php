<?php
namespace App;

class App extends \Jackbooted\Util\Module {
    const DB  = 'wp';
    const GST = 0.1;

    public static $dbPrefix = '';

    public static function init() {
        global $wpdb;
        if ( is_object( $wpdb ) ) {
            self::$dbPrefix = $wpdb->prefix . 'jack_';
        }
        else {
            self::$dbPrefix = 'wp_jack_';
        }
    }

    public static function getIP() {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'localhost';
        if ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $_SERVER ) ) {
            $ipAddress = array_pop( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        }
        return $ipAddress;
    }
    public static function debug() {
        error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );
        ini_set('display_errors', '1');
    }

}
