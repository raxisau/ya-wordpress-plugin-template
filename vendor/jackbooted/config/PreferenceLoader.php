<?php

namespace Jackbooted\Config;

use \Jackbooted\DB\DB;
use \Jackbooted\Config\Cfg;
use \Jackbooted\DB\DBTable;

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
class PreferenceLoader extends \Jackbooted\Util\JB {

    /** Preferences Object
     * @type Preferences
     */
    private $prefs;

    function __construct( $ser = NULL, $user = NULL ) {
        parent::__construct();
        //echo ( "<br>function LoadPrefs ( $ser=NULL, $user=NULL ) {" );
        $this->prefs = new Preferences ();

        // Make sure that we have the correct server
        $server = ( $ser == NULL ) ? Cfg::get( "server" ) : $ser;

        // Load up thi s domain information from
        // the user information If there is no information
        // in the user, check the alternate domain
        $sql = "SELECT * FROM tblUser WHERE ";
        if ( $user != NULL ) {
            $sql .= "fldUser='$user'";
        }
        else if ( $server != NULL ) {
            $sql .= "'$server' LIKE fldDomain";
        }
        $sql .= " LIMIT 1";

        if ( ! $this->_loadUserTable( $sql ) ) {
            $sql = "SELECT * FROM tblUser WHERE '$server' LIKE fldAltDomain LIMIT 1";
            if ( $this->_loadUserTable( $sql ) ) {
                // If it is in the alternate domain the
                // use these preferences
                $server = $this->prefs->userPrefs["fldDomain"];
                Cfg::set( 'server', $server );
            }
        }
    }

    function getPreferences() {
        return ( $this->prefs );
    }

    /**     Function to load the User information and from a Database table
     * @param $sql The SQL string that returns the "Table" to load
     * @returns boolean
     * @private
     */
    function _loadUserTable( $sql ) {
        // Create and Load the Table
        $tab = new DBTable( DB::DEF, $sql, null, DB::FETCH_ASSOC );

        // If Table is empty return false
        if ( $tab->isEmpty() ) {
            return ( FALSE );
        }

        // Load up the information
        foreach ( $tab as $row ) {
            foreach ( $row as $key => $val ) {
                switch ( $key ) {
                    case 'fldPicture':
                    case 'fldPhoto' : $typ = 'IMAGE';
                        break;
                    default: $typ = 'DATA';
                        break;
                }

                // Put the value and data type into the User Prefs/Types arrays
                $this->prefs->put( $key, $val, $typ );
            }
        }

        // return true/success
        return ( TRUE );
    }
}
