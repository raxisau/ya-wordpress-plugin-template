<?php

namespace Jackbooted\Config;

use \Jackbooted\Util\Log4PHP;

/** LoadPrefs.php - Loads up User Preferences
 *
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 *
 */
class Preferences extends \Jackbooted\Util\JB {

    const MODULE = "MODULE";
    const THEME = "THEME";
    const FUNC = "FUNCTION";
    const FILE = "FILE";
    const IMAGE = "IMAGE";
    const DATA = "DATA";

    public static $dataTypes = [ self::MODULE, self::THEME, self::FUNC, self::FILE, self::IMAGE, self::DATA ];
    private static $log;

    public static function init() {
        self::$log = Log4PHP::logFactory( __CLASS__ );
    }

    /** An array of User Preferences
     * @type Array
     * @private
     */
    public $userPrefs = [];
    private $userTypes = [];

    /** Function returns a User prefeernce
     * @param $s The key value of the User Prefernce to return
     * @returns var
     * @public
     */
    public function get( $s, $def = '' ) {
        //if not set return
        if ( !isset( $this->userTypes[$s] ) )
            return ( $def );

        // return the user preference
        return ( $this->userPrefs[$s] );
    }

    /**
     * function to return a list of all preferences of a particular type
     * @param $typ The type of prefeernces to return
     * @returns Array
     * @public
     */
    public function listType( $typ ) {
        $arr = [];
        foreach ( $this->userTypes as $key => $val ) {
            if ( $val == $typ )
                $arr[] = $key;
        }
        sort( $arr );
        return ( $arr );
    }

    /** Function loads the userPrefs/userTypes with values
     * @param $key The key ( name )
     * @param $val The value to save
     * @param $typ The type of the value
     * @public
     */
    public function put( $key, $val = FALSE, $typ = "DATA" ) {
        //if the $key is not set return
        if ( !isset( $key ) )
            return;

        // set the userPrefs/userTpes
        // If this is already in the system then just ignore type
        if ( !isset( $this->userPrefs[$key] ) ) {
            // if $typ is empty then set it to DATA
            if ( $typ == "" )
                $typ = "DATA";
            $this->userTypes[$key] = $typ;
        }

        $this->userPrefs[$key] = $val;
    }

}
