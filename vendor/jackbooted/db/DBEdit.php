<?php

namespace Jackbooted\DB;

use \Jackbooted\DB\DB;
use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\Html\Lists;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\WebPage;
use \Jackbooted\Html\Widget;
use \Jackbooted\Util\Invocation;
use \Jackbooted\Util\Log4PHP;

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
class DBEdit extends \Jackbooted\Util\JB {

    const TABLE_C   = 'TABLE_CLASS';
    const SUFFIX    = '_DE';
    const ACTION    = '_DEA';
    const DISPLAY   = 'DISPLAY';
    const HIDDEN    = 'HIDDEN';
    const NONE      = 'NONE';
    const SELECT    = 'SELECT';
    const RADIO     = 'RADIO';
    const ENCTEXT   = 'ENCTEXT';
    const TEXT      = 'TEXT';
    const TEXTAREA  = 'TEXTAREA';
    const TINYMCE   = 'TINYMCE';
    const CHECKBOX  = 'CHECKBOX';
    const TIMESTAMP = 'TIMESTAMP';
    const DATE      = 'DATE';
    const COLORPICK = 'COLORPICK';

    private $db;
    private $dbType;
    private $displayRows;
    private $suffix;
    private $formAction;
    private $canUpdate;
    private $canDelete;
    private $canInsert;
    private $insDefaults;

    private $displayType = [];
    private $cellAttributes = [];
    private $columnTitles = [];

    private $log;
    private $resp;
    private $ormClass;
    private $daoObject;
    private $selectSQL;
    private $defaultID;
    private $descCol;

    public static function factory( $ormClass, $selectSQL, $extraArgs = [] ) {
        return new DBEdit( $ormClass, $selectSQL, $extraArgs );
    }

    /**
     * Create the DBEdit Object.
     * @param string $tableName The name of the table
     * @param array $extraArgs This is the properties that the DBEdit will use to display/populate the database.
     * <pre>
     * </pre>
     */
    public function __construct( $ormClass, $selectSQL, $extraArgs = [] ) {
        parent::__construct();
        $this->log  = Log4PHP::logFactory( __CLASS__ );
        $this->resp = new Response ();

        $this->db          = ( isset( $extraArgs['db'] ) )          ? $extraArgs['db']          : DB::DEF;
        $this->dbType      = ( isset( $extraArgs['dbType'] ) )      ? $extraArgs['dbType']      : DB::driver( $this->db );
        $this->displayRows = ( isset( $extraArgs['displayRows'] ) ) ? $extraArgs['displayRows'] : 10;
        $this->suffix      = ( isset( $extraArgs['suffix'] ) )      ? $extraArgs['suffix']      : '_' . Invocation::next();
        $this->formAction  = ( isset( $extraArgs['formAction'] ) )  ? $extraArgs['formAction']  : '?';
        $this->insDefaults = ( isset( $extraArgs['insDefaults'] ) ) ? $extraArgs['insDefaults'] : [];
        $this->canDelete   = ( isset( $extraArgs['canDelete'] ) )   ? $extraArgs['canDelete']   : true;
        $this->canUpdate   = ( isset( $extraArgs['canUpdate'] ) )   ? $extraArgs['canUpdate']   : true;
        $this->canInsert   = ( isset( $extraArgs['canInsert'] ) )   ? $extraArgs['canInsert']   : true;
        $this->descCol     = ( isset( $extraArgs['descCol'] ) )     ? $extraArgs['descCol']     : 'fldDesc';

        $this->ormClass    = $ormClass;
        $daoClass          = $ormClass . 'DAO';
        $this->daoObject   = new $daoClass();
        $this->selectSQL   = $selectSQL;
        $this->action      = self::ACTION . $this->suffix;
        $this->submitId    = 'S' . $this->suffix;
        $this->defaultID   = $this->getDefaultID();

        $this->setupDefaultStyle();

        $this->copyVarsFromRequest( WebPage::ACTION );
    }

    public function index() {
        $htmlController = $this->controller();

        if ( ( $id = Request::get( $this->daoObject->primaryKey, $this->defaultID ) ) == '' ) {
            return "No Default ID ($this->daoObject->primaryKey})";
        }

        $tmpTable = 't_' . md5(mt_rand());
        if ( stripos( $this->selectSQL, 'ORDER BY' ) !== false ) {
            $sql = 'SELECT * FROM ( ' . str_ireplace('ORDER BY', ") {$tmpTable} ORDER BY", $this->selectSQL );
        }
        else {
            $sql = "SELECT * FROM ( {$this->selectSQL} ) {$tmpTable}";
        }

        $listSelect = Lists::select ( $this->daoObject->primaryKey, $sql,
                                      [ 'size' => $this->displayRows,'onClick' => 'submit();', 'default' => $id, 'DB' => $this->db ] );

        $html = Tag::table( array_merge( [ 'id' => 'DBEdit' . $this->suffix ], $this->styles[self::TABLE_C] ) ) .
                  Tag::tr() .
                    Tag::td( ['valign' => 'top'] ) .
                      '<H4>Click on item</h4>' .
                      Tag::form( [ 'action' => $this->formAction ] ) .
                        $this->resp->set( $this->action, 'dummyClick' )->toHidden ( false ) .
                        $listSelect .
                      Tag::_form () .
                    Tag::_td() .
                    Tag::td( [ 'widdth' => '100%', 'valign' => 'top' ] ) .
                      $this->indexItem( $id ) .
                    Tag::_td() .
                  Tag::_tr();
        if ( $this->canInsert ) {
            $sep = ( $this->formAction == '?' ) ? '' : '&';
            $html .=
                  Tag::tr() .
                    Tag::td( ['colspan' => '10'] ) .
                      Tag::linkButton( $this->formAction . $sep . $this->resp->set( $this->action, 'insertBlank' )->toUrl(), 'Insert Blank' ) .
                    Tag::_td() .
                  Tag::_tr();
        }
        $html .=Tag::_table();

        return Widget::styleTable( '#DBEdit' . $this->suffix ) .
               $htmlController.
               $html;
    }

    public function dummyClick() {
        return '';
    }

    public function insertBlank() {
        $ormClass = $this->ormClass;
        $ormObject = $ormClass::create( $this->insDefaults );
        Request::set( $this->daoObject->primaryKey, $ormObject->id );
        return Widget::popupWrapper( "Inserted one object ID:{$ormObject->id}" );
    }

    private function indexItem( $id ) {
        $ormClass = $this->ormClass;
        $ormObject = $ormClass::load( $id )->copyToRequest();
        foreach ( $ormObject->getData() as $col => $val ) {
            $this->columnTitles[$col]   = $this->convertColumnToTitle( $col );
            $this->cellAttributes[$col] = [];
            $this->calculateColumnWidth( $col, $val );
        }

        $resp = $this->resp->set( $this->daoObject->primaryKey, $id );

        $html = Tag::form( [ 'action' => $this->formAction ] ) .
                  $resp->set( $this->action, 'save' )->toHidden( ) .
                  Tag::table();

        foreach ( $ormObject->getData() as $key => $value ) {
            $html .= $this->renderValue( $key, $value );
        }

        $html .=    Tag::tr() .
                      Tag::td([ 'colspan' => 10]) .
                        Tag::submit( 'Save' );

        $sep = ( $this->formAction == '?' ) ? '' : '&';
        if ( $this->canInsert ) {
            $html .=    Tag::linkButton( $this->formAction . $sep . $this->resp->set( $this->action, 'dup' )->toUrl(), 'Dup' );
        }
        if ( $this->canDelete ) {
            $html .=    Tag::linkButton( $this->formAction . $sep . $this->resp->set( $this->action, 'del' )->toUrl(), 'Del' );
        }

        $html .=      Tag::_td() .
                    Tag::_tr() .
                  Tag::_table() .
                Tag::_form ();

        return $html;
    }

    private function renderValue( $colName, $value ) {
        $html = '';
        $tinyMCEJS = '';
        $dateJS = '';

        $type = $this->getColumnType( $colName );
        $updCheckAttrib = [];

        switch ( $type ) {
            case self::NONE:
                break;

            case self::DISPLAY:
                $html .= ( $value == '' ) ? '&nbsp;' : Tag::e( $value );
                break;

            case self::HIDDEN:
                $this->resp->set( $colName, $value );
                break;

            case self::RADIO:
                $dispList = ( isset( $this->displayType[$colName][1] ) ) ? $this->displayType[$colName][1] : null;
                $updCheckAttrib['default'] = $value;
                $updCheckAttrib['DB'] = $this->db;

                $html .= Tag::table() .
                           Tag::tr() .
                             Tag::td( [ 'nowrap' => 'nowrap' ] ) .
                               implode( Tag::_td() . Tag::td( [ 'nowrap' => 'nowrap' ] ), Lists::radio( $colName, $dispList, $updCheckAttrib ) ) .
                             Tag::_td() .
                           Tag::_tr() .
                         Tag::_table();
                break;

            case self::SELECT:
                $dispList  = ( isset( $this->displayType[$colName][1] ) ) ? $this->displayType[$colName][1] : null;
                $blankLine = ( isset( $this->displayType[$colName][2] ) ) ? $this->displayType[$colName][2] : false;
                $updCheckAttrib['default'] = $value;
                $updCheckAttrib['hasBlank'] = $blankLine;
                $updCheckAttrib['DB'] = $this->db;
                $html .= Lists::select( $colName, $dispList, $updCheckAttrib );
                break;

            case self::COLORPICK:
                $dispList  = ( isset( $this->displayType[$colName][1] ) ) ? $this->displayType[$colName][1] : null;
                $html .= self::colorPicker( $colName, $dispList, $value );
                break;

            case self::CHECKBOX:
                $checkValue = ( isset( $this->displayType[$colName][1] ) ) ? $this->displayType[$colName][1] : 'YES';
                $html .= Tag::checkBox( $colName, $checkValue, $value == $checkValue );
                break;

            case self::TIMESTAMP:
                $attribs = array_merge( $updCheckAttrib, $this->cellAttributes[$colName] );
                $attribs['value'] = date( 'Y-m-d H:i:s', (int) $value );
                $attribs['size'] = strlen( $attribs['value'] ) + 1;
                $html .= Tag::text( $colName, $attribs );
                break;

            case self::TEXTAREA:
                $attribs = array_merge( [ 'rows' => 5, 'style' => 'width:100%;' ], $updCheckAttrib, $this->cellAttributes[$colName] );
                $html .= Tag::textArea( $colName, $value, $attribs );
                break;

            case self::TINYMCE:
                if ( $tinyMCEJS == '' ) {
                    $tinyMCEJS = Widget::tinyMCE( '.dbedit_tinymce' );
                }

                $attribs = array_merge( [ 'rows' => '10',
                                          'style' => 'width:100%;',
                                          'class' => 'dbedit_tinymce',
                                          'title' => 'Edit this field' ], $updCheckAttrib, $this->cellAttributes[$colName] );
                $html .= Tag::textArea ( $colName, $value, $attribs );
                break;

            case self::ENCTEXT:
                $value = Cryptography::de( (string) $value );
                // Fall through to output text field

            case self::DATE:
                if ( $dateJS == '' ) {
                    $dateJS = Widget::datePickerJS( 'input.datepicker' );
                }
                $updCheckAttrib['class'] = 'datepicker';
                $html .= Tag::text( $colName, array_merge( $updCheckAttrib, $this->cellAttributes[$colName] ) );
                break;

            case self::TEXT:
            default:
                $updCheckAttrib['value'] = (string) $value;
                $updCheckAttrib['style'] = 'width:100%;';
                $html .= Tag::text( $colName, array_merge( $updCheckAttrib, $this->cellAttributes[$colName] ) );
                break;
        }

        if ( !in_array( $type, [ self::HIDDEN, self::NONE ] ) ) {
            $html = Tag::tr() .
                      Tag::td( [ 'valign' => 'top' ] ) . $this->convertColumnToTitle( $colName ) . Tag::_td() .
                      Tag::td() . $html .Tag::_td() .
                    Tag::_tr();
        }

        return $tinyMCEJS .
               $dateJS .
               $html;
    }

    private function getColumnType( $colName ) {
        if ( isset( $this->displayType[$colName] ) ) {
            if ( is_string( $this->displayType[$colName] ) ) {
                $type = $this->displayType[$colName];
            }
            else {
                $type = $this->displayType[$colName][0];
            }
        }
        else if ( $colName == $this->daoObject->primaryKey ) {
            $type = self::DISPLAY;
        }
        else {
            $type = self::TEXT;
        }

        if ( ! $this->canUpdate && ! in_array( $type, [ self::HIDDEN, self::NONE ] ) ) {
            $type = self::DISPLAY;
        }
        return $type;
    }

    public function dup( ) {
        if ( ( $id = Request::get( $this->daoObject->primaryKey ) ) == '' ) {
            return Widget::popupWrapper( 'Error. Invalid Object ID' );
        }

        $ormClass = $this->ormClass;
        $data = $ormClass::load( $id )->getData();
        if ( isset( $data[$this->descCol] ) ) {
            $data[$this->descCol] .= ' Copy';
        }
        $oldAttribute = DB::setBuffered( $this->db, false );
        $ormObject = $ormClass::create( $data );
        $ormObject->commit();
        DB::setBuffered( $this->db, $oldAttribute );
        Request::set( $this->daoObject->primaryKey, $ormObject->id );
        return Widget::popupWrapper( "Created duplicate row {$ormObject->id}" );
    }

    public function del( ) {
        if ( ( $id = Request::get( $this->daoObject->primaryKey ) ) == '' ) {
            return Widget::popupWrapper( 'Error. Invalid Object ID' );
        }

        $ormClass = $this->ormClass;
        $oldAttribute = DB::setBuffered( $this->db, false );
        $ormObject = $ormClass::load( $id );
        if ( $ormObject->delete() !== false ) {
            $ormObject->commit();
            Request::set( $this->daoObject->primaryKey, $this->getDefaultID() );
            $html = Widget::popupWrapper( "Sucessfully deleted row {$ormObject->id}" );
        }
        else {
            $html = Widget::popupWrapper( "ERROR: Unable to delete row {$ormObject->id}" );
        }
        DB::setBuffered( $this->db, $oldAttribute );
        return $html;
    }
    public function save( ) {
        if ( ( $id = Request::get( $this->daoObject->primaryKey ) ) == '' ) {
            return Widget::popupWrapper( 'Error. Invalid Object ID' );
        }

        $ormClass = $this->ormClass;
        $oldAttribute = DB::setBuffered( $this->db, false );
        $ormObject = $ormClass::load( $id );
        $ormObject->copyFromRequest()->save();
        $ormObject->commit();
        DB::setBuffered( $this->db, $oldAttribute );

        return Widget::popupWrapper( 'Saved Item ' . $id );
    }


    public function copyVarsFromRequest( $v ) {
        $this->resp->copyVarsFromRequest( $v );
        return $this;
    }

    /**
     * Sets up custom display for columns
     * @param string $colName
     * @param mixed $colStyle
     * @return DBEdit current instance for chaining
     * <pre>
     * $crud->setColDisplay ( 'fldUserID',      array ( DBEdit::SELECT, 'SELECT id,username FROM tblUser', $displayBlank ) )
     * $crud->setColDisplay ( 'fldGroupID',     array ( DBEdit::SELECT, self::GROUP_SQL, true ) )
     * $crud->setColDisplay ( 'fldLevelID',     array ( DBEdit::SELECT, array ( 1, 2, 3 ), true ) )
     * $crud->setColDisplay ( 'fldPrivilegeID',  DBEdit::DISPLAY )
     * </./pre>
     */
    public function setColDisplay( $colName, $colStyle ) {
        $this->displayType[$colName] = $colStyle;
        return $this;
    }

    public function setProperty( $name, $value ) {
        $this->$name = $value;
        return $this;
    }

    public function getProperty( $name ) {
        return $this->$name;
    }

    public function columnAttrib( $col, $attrib = [] ) {
        foreach ( $attrib as $key => $val ) {
            $this->cellAttributes[$col][$key] = $val;
        }
        return $this;
    }

    public function style( $type, $attribs = null ) {
        if ( $attribs === null ) {
            unset( $this->styles[$type] );
        }
        else {
            $this->styles[$type] = $attribs;
        }
        return $this;
    }

    private function setupDefaultStyle() {
        $this->styles[self::TABLE_C] = [ 'cellpadding' => 1, 'cellspacing' => 0, 'border' => 1 ];
    }

    public static function colorPicker ( $name, $colorList, $default ) {
        // TODO The style does not work. The nest thing to do would be create jquery that
        // will update an example.
        $id = 'colorPicker_' . Invocation::next();
        $idSel = 'colorPickerSel_' . Invocation::next();
        $exampleStyle = '';

        //$html = "<select name=\"$name\" onchange=\"$('#{$id}').attr('style',$(this).attr('style'))\">";
        $html = "<select id=\"$idSel\" name=\"$name\" onchange=\"$('#{$id}').attr('style', $('#{$idSel} option:selected').attr('style'))\">";
        foreach ( $colorList as $colItem ) {
            $colName = $colItem[0] . "|" . $colItem[1];
            if ( $colName == $default ) {
                $selected = 'selected="selected"';
                $exampleStyle = "color:{$colItem[0]}; background-color:{$colItem[1]};";
            }
            else {
                $selected = '';
            }

            $selected = ( $colName == $default ) ? 'selected="selected"' : '';
            $html .= "<option value=\"{$colName}\"  style=\"color:{$colItem[0]}; background-color:{$colItem[1]};\" $selected>{$colName}</option>";
        }
        $html .= "</select>&nbsp;&nbsp;<span id=\"$id\" style=\"$exampleStyle\">Example</span>";



        return $html;
    }

    private function convertColumnToTitle( $col ) {
        if ( $col == $this->daoObject->primaryKey ) {
            return 'ID';
        }

        $title = '';

        if ( substr( $col, 0, 3 ) == 'fld' ) {
            $title = self::jbCol2Title( $col );
        }
        else if ( substr( $col, 0, 2 ) == 'f_' ) {
            foreach ( explode( '_', substr( $col, 2 ) ) as $segment ) {
                if ( $title != '' ) {
                    $title .= ' ';
                }
                $title .= ucfirst( $segment );
            }
        }
        else {
            foreach ( explode( '_', $col ) as $segment ) {
                if ( $title != '' ) {
                    $title .= ' ';
                }
                $title .= ucfirst( $segment );
            }
        }
        return $title;
    }

    private static function jbCol2Title( $colP ) {
        $col = substr( $colP, 3 );
        $title = '';
        $colLen = strlen( $col );
        $lastCharacterIsUpper = true;
        for ( $i = 0; $i < $colLen; $i++ ) {
            $ch = substr( $col, $i, 1 );
            $curCharacterIsUpper = ctype_upper( $ch );
            if ( $curCharacterIsUpper && !$lastCharacterIsUpper ) {
                $title .= ' ';
            }
            $lastCharacterIsUpper = $curCharacterIsUpper;
            $title .= $ch;
        }
        return $title;
    }
    private function controller() {
        if ( ( $action = Request::get( $this->action ) ) == '' ) {
            return '';
        }
        Request::set( $this->action, ''  );
        if ( ! method_exists( $this, $action ) ) {
            return "Method: $action does not exist";
        }

        return $this->$action();
    }
    private function getDefaultID() {
        foreach ( DBTable::factory( $this->db, $this->selectSQL ) as $row ) {
            return $row[0];
        }
        return false;
    }
    private function calculateColumnWidth( $colName, $value ) {
        if ( isset( $this->cellAttributes[$colName]['size'] ) ) {
            return;
        }

        $width = strlen( $value );
        if ( $width > 40 ) {
            $width = 40;
        }

        if ( $width >= 0 && $width <= 40 ) {
            $this->cellAttributes[$colName]['size'] = $width;
        }
    }
}
