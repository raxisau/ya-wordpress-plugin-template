<?php

namespace Jackbooted\Config;

use \Jackbooted\Admin\Login;
use \Jackbooted\Forms\CRUD;
use \Jackbooted\G;
use \Jackbooted\Html\WebPage;

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

/**
 *
 */
class AdminConfig extends WebPage {

    const DEF = '\Jackbooted\Config\AdminConfig->index()';

    public function index() {
        if ( !G::isLoggedIn() ) {
            return Login::controller( Login::DEF );
        }

        $crud = new CRUD( 'tblConfig', [ 'insDefaults' => [ 'fldUserID' => G::getUserID() ] ] );
        $crud->setColDisplay( 'fldUserID', CRUD::HIDDEN );
        $crud->columnAttrib( 'fldValue', [ 'size' => 60 ] );

        return $crud->index();
    }

}
