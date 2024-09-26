<?php

namespace Jackbooted\Html;

use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBTable;
use \Jackbooted\Forms\Request;

/**
 * @copyright Confidential and copyright (c) 2024 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 *
 */

/**
 * This class generates the HTML for lists. Value add to the basic tags
 */
class Lists extends \Jackbooted\Util\JB {

    /**
     * Generates a drop down box from almost anything
     * @param string $name nameof the select
     * @param array $displayList
     * @param array $attribs html attributes to generate
     * @param string $defaultValue matches the key in the displayList
     * @param boolean $blank true if you want to generate a blank row
     * @returns string The resulting HTML
     */
    static function select( $name, $displayList = null, $attribs = array() ) {

        $blank = false;
        if ( isset( $attribs['hasBlank'] ) ) {
            $blank = $attribs['hasBlank'];
        }

        $db = DB::DEF;
        if ( isset( $attribs['DB'] ) ) {
            $db = $attribs['DB'];
            unset( $attribs['DB'] );
        }

        // If an array is here
        if ( is_array( $displayList ) && ( count( $displayList ) > 0 || $blank ) ) {
            if ( isset( $attribs['size'] ) && $attribs['size'] > count( $displayList ) ) {
                $attribs['size'] = count( $displayList );
            }

            if ( isset( $attribs['default'] ) ) {
                $defaultValue = $attribs['default'];
                unset( $attribs['default'] );
            }
            else {
                $defaultValue = Request::get( $name, null );
            }

            $blank = false;
            if ( isset( $attribs['hasBlank'] ) ) {
                $blank = $attribs['hasBlank'];
                unset( $attribs['hasBlank'] );
            }

            $blankName = '';
            if ( isset( $attribs['blankName'] ) ) {
                $blankName = $attribs['blankName'];
                unset( $attribs['blankName'] );
            }

            $tag = Tag::select( $name, $attribs );
            if ( !is_array( $defaultValue ) ) {
                $defaultValue = array( $defaultValue );
            }
            if ( $blank ) {
                $tag .= Tag::optiontag( '', $blankName, in_array( '', $defaultValue ) );
            }
            foreach ( $displayList as $key => $val ) {
                if ( is_int( $key ) ) {
                    $key = $val;
                }
                $key = trim( $key );
                $tag .= Tag::optiontag( $key, $val, in_array( $key, $defaultValue ) );
            }
            $tag .= Tag::_select();
        }

        // If this is a DBTable object
        else if ( is_object( $displayList ) && $displayList instanceof DBTable ) {
            $newDisplayList = array();
            for ( $i = 0; $i < $displayList->getRowCount(); $i++ ) {
                $key = $displayList->getValue( 0, $i );
                $val = ( $displayList->getColumnCount() > 1 ) ? $displayList->getValue( 1, $i ) : $key;
                $newDisplayList[' ' . $key] = $val;
            }
            $tag = self::select( $name, $newDisplayList, $attribs );
        }

        // If this is a sql string
        else if ( is_string( $displayList ) ) {
            $table = new DBTable( $db, $displayList, null, DB::FETCH_NUM );
            $tag = self::select( $name, $table, $attribs );
        }

        // Default to nothing
        else {
            $tag = "**None Available**";
            if ( isset( $attribs['default'] ) ) {
                $tag .= Tag::hidden( $name, $attribs['default'] );
            }
        }
        return $tag;
    }

    /**
     * Generates a drop down box from almost anything
     * @param string $name nameof the select
     * @param array $displayList
     * @param array $attribs html attributes to generate
     * @param string $defaultValue matches the key in the displayList
     * @param boolean $blank true if you want to generate a blank row
     * @returns string The resulting HTML
     */
    static function selectWithCategories( $name, $resultset, $attribs = array() ) {
        if ( isset( $attribs['default'] ) ) {
            $defaultValue = $attribs['default'];
            unset( $attribs['default'] );
        }
        else {
            $defaultValue = Request::get( $name, null );
        }

        $hasBlank = false;
        if ( isset( $attribs['hasBlank'] ) ) {
            $hasBlank = $attribs['hasBlank'];
            unset( $attribs['hasBlank'] );
        }

        $optGroupAttrib = array();
        if ( isset( $attribs['optGroupAttrib'] ) ) {
            $optGroupAttrib = $attribs['optGroupAttrib'];
            unset( $attribs['optGroupAttrib'] );
        }

        if ( is_array( $resultset ) ) {
            if ( count( $resultset ) == 0 ) {
                $tag = "* None Available *";
                if ( $defaultValue != null ) {
                    $tag .= Tag::hidden( $name, $defaultValue );
                }
            }
            else {
                $tag = Tag::select( $name, $attribs );
                if ( $hasBlank ) {
                    $tag .= Tag::optiontag( ' ', '', (!isset( $defaultValue ) || $defaultValue == false ) );
                }
                foreach ( $resultset as $category => $list ) {
                    $tag .= Tag::hTag( 'optgroup', array_merge( $optGroupAttrib, array( 'label' => $category ) ) );
                    foreach ( $list as $key => $val ) {
                        $tag .= Tag::optiontag( trim( $key ), $val, ( $defaultValue == $key ) );
                    }
                    $tag .= Tag::_hTag( 'optgroup' );
                }
                $tag .= Tag::_select();
            }
        }
        else if ( is_object( $resultset ) ) {
            /** FIXME * */
            $table = new DBTable( $resultset, DB::FETCH_NUM );
            if ( $table->rowCount() == 0 ) {
                $tag = "* None Available *";
                if ( $defaultValue != null ) {
                    $tag .= Tag::hidden( $name, $defaultValue );
                }
            }
            else {
                $tag = Tag::select( $name, $attribs );
                if ( $hasBlank ) {
                    $tag .= Tag::optiontag( ' ', '', (!isset( $defaultValue ) || $defaultValue == false ) );
                }
                $prevCategory = '';
                foreach ( $table as $row ) {
                    if ( $prevCategory != $row[0] ) {
                        if ( $prevCategory != '' ) {
                            $tag .= Tag::_hTag( 'optgroup' );
                        }
                        $tag .= Tag::hTag( 'optgroup', array( 'label' => $row[0] ) );
                        $prevCategory = $row[0];
                    }
                    $tag .= Tag::optiontag( $row[1], $row[2], ( $defaultValue == $row[1] ) );
                }
                $tag .= Tag::_hTag( 'optgroup' );
                $tag .= Tag::_select();
            }
        }

        return $tag;
    }

    /**
     * Generates a radio awlwct box from almost anything
     * @param array $displayList
     * @param array $attribs html attributes to generate
     * @param string $defaultValue matches the key in the displayList
     * @param boolean $blank true if you want to generate a blank row
     * @returns string The resulting HTML
     */
    static function radio( $name, $displayList, $attribs = array() ) {

        // If an array is here
        if ( is_array( $displayList ) && count( $displayList ) > 0 ) {
            if ( isset( $attribs['side'] ) ) {
                $side = $attribs['side'];
                unset( $attribs['side'] );
            }
            else {
                $side = 'left';
            }

            if ( isset( $attribs['default'] ) ) {
                $defaultValue = $attribs['default'];
                unset( $attribs['default'] );
            }
            else {
                $defaultValue = Request::get( $name, null );
            }

            $tag = array();
            $idx = 0;
            foreach ( $displayList as $key => $val ) {
                if ( is_int( $key ) ) {
                    $key = $val;
                }
                $key = trim( $key );

                $attribs['id'] = $name . $idx ++;
                $label = Tag::label( $attribs['id'], ucwords( strtolower( $val ) ) );
                $radio = Tag::radio( $name, $key, ( $defaultValue == $key ), $attribs );
                if ( $side == 'left' ) {
                    $tag[$attribs['id']] = $label . '&nbsp;' . $radio;
                }
                else {
                    $tag[$attribs['id']] = $radio . '&nbsp;' . $label;
                }
            }
        }

        // If this is a DBTable object
        else if ( is_object( $displayList ) && $displayList instanceof DBTable ) {
            $newDisplayList = array();
            for ( $i = 0; $i < $displayList->getRowCount(); $i++ ) {
                $key = $displayList->getValue( 0, $i );
                $val = ( $displayList->getColumnCount() > 1 ) ? $displayList->getValue( 1, $i ) : $key;
                $newDisplayList[' ' . $key] = $val;
            }
            $tag = self::radio( $name, $newDisplayList, $attribs );
        }

        // If this is a sql string
        else if ( is_string( $displayList ) ) {
            $table = new DBTable( DB::DEF, $displayList, null, DB::FETCH_NUM );
            $tag = self::radio( $name, $table, $attribs );
        }

        // Default to nothing
        else if ( isset( $attribs['default'] ) ) {
            $tag = Tag::hidden( $name, $attribs['default'] );
        }
        else {
            $tag = false;
        }
        return $tag;
    }

    /** Creates a Dual Select windows
     * @param string $lName Left Column name
     * @param array $lList Left Column name
     * @param string $rName Right Column name
     * @param array $rList Right Column name
     * @param int $ht Height
     * @returns String The Html pf a dual select
     */
    public static function dualSelect( $lName, $lList, $rName, $rList, $ht = 8 ) {
        $title = array( "title" => "Select items is the left list to move to the selected list. " .
            "Move items in the right list to remove from the selected list. " .
            "Select buttons in the moddle to move all the items in the list" );

        $msg = Tag::table( $title ) .
                 Tag::tr() .
                   Tag::td( "align=center" ) .
                     "Out of List<br>" .
                     Lists::select( $lName, $lList, "onChange=\"selMove( '$lName','$rName',false );\" Size=$ht Multiple" ) .
                   Tag::_td() .
                   Tag::td( "valign=middle" ) .
                     "<br>" . Tag::button( ">>", "onClick=\"selMove( '$lName','$rName',true );\"" ) .
                     "<br>" . Tag::button( "<<", "onClick=\"selMove( '$rName','$lName',true );\"" ) .
                   Tag::_td() .
                   Tag::td( "align=center" ) .
                     "In the List<br>" .
                     Lists::select( $rName, $rList, "onChange=\"selMove( '$rName','$lName',false );\" Size=$ht Multiple" ) .
                     Tag::hidden( $rName . "Result" ) .
                   Tag::_td() .
                 Tag::_tr() .
               Tag::_table();

        return ( $msg );
    }

}
