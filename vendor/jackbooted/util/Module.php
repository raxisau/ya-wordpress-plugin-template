<?php

namespace Jackbooted\Util;

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
class Module extends \Jackbooted\Util\JB {

    const CRUD_MOD = 'crud';

    public static function crud( \Jackbooted\Forms\CRUD &$crud ) {
        return $crud;
    }

}
