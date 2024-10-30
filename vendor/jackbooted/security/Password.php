<?php
namespace Jackbooted\Security;

/*
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
 * Description of Encryption
 *
 * @author bdutton
 */
class Password extends \Jackbooted\Util\JB {

    /** Function to Generate a Password
     * @param $str Strength of the password
     * @param $len Length of the password
     * @returns var
     * @public
     */
    const LOWER_ALPHA = 1;
    const UPPER_ALPHA = 2;
    const NUMBERS = 4;
    const SYMBOLS = 8;
    const SIMPLE = 3; // 1 | 2;
    const MEDIUM = 7; // 1 | 2 | 4
    const COMPLEX = 15; // 1 | 2 | 4 | 8;

    public static function passGen( $len = 8, $complexity = self::COMPLEX ) {
        // This is the array of group indexes. The reason that theere are 2 alpha
        // for upper and lower is that this gives a higher density of alpha
        // For example passGen ( 6, self::COMPLEX ) will give 2 upper case,
        // 2 lowercase one number and one symbol. This ratio seems to be the most readable
        // while keeping the password strength
        $letterGroupIndexes = [
            self::LOWER_ALPHA,
            self::LOWER_ALPHA,
            self::UPPER_ALPHA,
            self::UPPER_ALPHA,
            self::NUMBERS,
            self::SYMBOLS
        ];

        // These letter groups are chosen to remove the ambigious letters
        // In this case G l I 1 0 O o are all a little ambigious
        // Also the letter w is removed. In some fonts it is hard to read
        $letterGroups = [
            self::LOWER_ALPHA => 'abcdefghijkmnpqrstuvxyz',
            self::UPPER_ALPHA => 'ABCDEFHJKLMNPRSTUVXYZ',
            self::NUMBERS => '23456789',
            self::SYMBOLS => '@#$%^&*+?{}[]<>'
        ];
        $password = '';

        for ( $i = 0; strlen( $password ) < $len; $i++ ) {
            $idx = $letterGroupIndexes[$i % count( $letterGroupIndexes )];

            // Skip this group is the password is too complex
            if ( ( $idx & $complexity ) == 0 ) {
                continue;
            }

            $password .= substr( str_shuffle( $letterGroups[$idx] ), 0, 1 );
        }
        return str_shuffle( $password );
    }

}
