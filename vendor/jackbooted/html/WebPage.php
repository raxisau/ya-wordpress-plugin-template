<?php
namespace Jackbooted\Html;

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
class WebPage extends \Jackbooted\Util\JB {

    const ACTION   = '_ACT';
    const SAVE_URL = '_SAVEURL';

    protected static $log;

    public static function init() {
        self::$log = \Jackbooted\Util\Log4PHP::logFactory( static::class );
    }

    public static function controller( $default = '', $actionKey = self::ACTION ) {
        $action = \Jackbooted\Forms\Request::get( $actionKey, $default );
        if ( ! isset( $action ) || $action == false || $action == '' ) {
            return false;
        }

        return self::execAction( $action );
    }

    protected static function execAction( $action ) {
        if ( strpos( $action, '::' ) !== false ) {
            list ( $clazz, $rest ) = explode( '::', $action );
            list ( $className, $functionName ) = self::normalizeCall( $clazz, $rest );
            $html = call_user_func( [ $className, $functionName ] );
        }
        else if ( strpos( $action, '->' ) !== false ) {
            list ( $clazz, $rest ) = explode( '->', $action );
            list ( $className, $functionName ) = self::normalizeCall( $clazz, $rest );
            $html = call_user_func( [ new $className(), $functionName ] );
        }
        else {
            $cName = static::class;
            $object = new $cName ();

            if ( method_exists( $object, $action ) ) {
                $html = $object->$action();
            }
            else {
                $html = $object->index();
            }
        }
        return $html;
    }

    private static function normalizeCall( $clazz, $rest ) {
        $className = str_replace( '\\\\', '\\', $clazz );
        if ( ( $idx = strpos( $rest, '(' ) ) !== false ) {
            $functionName = trim( substr( $rest, 0, $idx ) );
        }
        else {
            $functionName = $rest;
        }
        return [ $className, $functionName ];
    }

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        return '<pre>' . var_export( $_REQUEST, false ) . '</pre>';
    }

    public function blank() {
        return '';
    }

    public function __call( $name, $arguments ) {
        $fName = \Jackbooted\Config\Cfg::get( 'site_path' ) . '/' . $name . '.html';
        if ( file_exists( $fName ) ) {
            return file_get_contents( $fName );
        }
        else {
            return 'Unknown Method Call: ' . $name;
        }
    }
}
