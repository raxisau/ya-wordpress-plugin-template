<?php

namespace Jackbooted\Forms;

use \Jackbooted\Html\Tag;

/**
 * @copyright Confidential and copyright (c) 2024 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */
abstract class Navigator extends \Jackbooted\Util\JB {

    // This needs to be public because it is set directly in the sub classes
    public $respVars;
    protected $attribs;
    protected $formVars;
    protected $navVar;
    public    $action = '?';

    public function __construct() {
        parent::__construct();
    }

    /**
     * @param  $key
     * @param  $value
     * @return Navigator
     */
    public function set( $key, $value ) {
        $this->formVars[$key] = $value;
        return $this;
    }

    /**
     * @param  $key
     * @return Response
     */
    public function get( $key ) {
        if ( isset( $this->formVars[$key] ) ) {
            return $this->formVars[$key];
        }
        return '';
    }

    /**
     * @return
     */
    public function getResponse() {
        return $this->respVars;
    }

    public function copyVarsFromRequest( $v ) {
        $this->respVars->copyVarsFromRequest( $v );
        return $this;
    }

    /**
     * @param  $startingRow
     * @return string
     */
    protected function toUrl() {
        $this->respVars->set( $this->navVar, $this->formVars );
        return $this->action . $this->respVars->toUrl();
    }

    protected function toHidden( $exemptVars ) {
        $hiddenVars = $this->respVars->del( $this->navVar )->toHidden( false );

        foreach ( $this->formVars as $key => $val ) {
            if ( !in_array( $key, $exemptVars ) ) {
                $hiddenVars .= Tag::hidden( $this->toFormName( $key ), $val );
            }
        }

        return $hiddenVars;
    }

    protected function toFormName( $key ) {
        return $this->navVar . '[' . $key . ']';
    }

}
