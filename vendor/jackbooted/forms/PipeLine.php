<?php

namespace Jackbooted\Forms;

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

/**
 * Class for Managing Form Variables
 */
abstract class PipeLine extends \Jackbooted\Util\JB implements \Iterator {

    protected static $log;

    public static function init() {
        self::$log = \Jackbooted\Util\Log4PHP::logFactory( __CLASS__ );
    }

    protected $formVars = [];

    public function __construct() {
        parent::__construct();
    }

    public function clear() {
        $this->formVars = [];
    }

    public function getRaw( $key ) {
        $keyList = explode( '.', str_replace( [ '[', ']' ], [ '', '' ], str_replace( '][', '.', $key ) ) );
        $value = $this->formVars;
        foreach ( $keyList as $k ) {
            if ( ! isset( $value[$k] ) ) {
                return '';
            }

            $value = $value[$k];
        }
        return $value;
    }

    public function count() {
        return count( $this->formVars );
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return array
     */
    public function current() : mixed {
        return current( $this->formVars );
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return integer
     */
    public function key() : mixed {
        return key( $this->formVars );
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return void
     */
    public function next() : void {
        next( $this->formVars );
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return void
     */
    public function rewind() : void {
        reset( $this->formVars );
    }

    /**
     * Iterator function.
     *
     * @since 1.0
     * @return boolean
     */
    public function valid() : bool {
        return current( $this->formVars ) !== false;
    }

    public function dump( ) {
        echo $this->__toString();
        return $this;
    }

    public function __toString() {
        return "<pre>\n" . print_r ( $this->formVars, true ) . '</pre>';
    }
}
