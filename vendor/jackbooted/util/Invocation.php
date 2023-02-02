<?php

namespace Jackbooted\Util;

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
class Invocation extends \Jackbooted\Util\JB {

    private static $dbtabInvocations = 0;

    public static function next() {
        $id = self::$dbtabInvocations++;
        return $id;
    }

}
