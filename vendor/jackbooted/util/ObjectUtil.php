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
class ObjectUtil {

    public static function toArray( $d ) {
        if ( is_object( $d ) ) {
            // Gets the properties of the given object with get_object_vars function
            $d = get_object_vars( $d );
        }

        if ( is_array( $d ) ) {
            // Return array converted to object for recursive call
            return array_map( __METHOD__, $d );
        }
        else {
            // Return array
            return $d;
        }
    }

}
