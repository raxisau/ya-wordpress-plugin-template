<?php

namespace Jackbooted\Forms;

use \Jackbooted\Config\Cfg;
use \Jackbooted\DB\DB;
use \Jackbooted\DB\DBMaintenance;
use \Jackbooted\DB\DBTable;
use \Jackbooted\Html\JS;
use \Jackbooted\Html\Lists;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\WebPage;
use \Jackbooted\Html\Widget;
use \Jackbooted\Security\Cryptography;
use \Jackbooted\Util\Invocation;
use \Jackbooted\Util\Log4PHP;
use \Jackbooted\Util\StringUtil;

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

/**
 *
 */
class CRUD extends \Jackbooted\Util\JB {

    const TABLE_C = 'TABLE_CLASS';
    const SUFFIX = '_C';
    const ACTION = '_CA';
    const DISPLAY = 'DISPLAY';
    const HIDDEN = 'HIDDEN';
    const NONE = 'NONE';
    const SELECT = 'SELECT';
    const RADIO = 'RADIO';
    const ENCTEXT = 'ENCTEXT';
    const TEXT = 'TEXT';
    const CHECKBOX = 'CHECKBOX';
    const TIMESTAMP = 'TIMESTAMP';

    private static $headerDisplayed = false;
    protected $tableName;
    protected $primaryKey;
    protected $db;
    protected $log;
    protected $columnTitles = [];
    protected $cellAttributes = [];
    protected $styles = [];
    protected $displayType = [];
    protected $where;
    protected $extraCols;
    protected $canDelete;
    protected $canUpdate;
    protected $canInsert;
    protected $topPage;
    protected $bottomPage;
    protected $action;
    protected $suffix;
    protected $delTag;
    protected $updTag;
    protected $gridTag;
    protected $submitId;
    protected $formAction;
    protected $insDefaults;
    protected $dbType;
    protected $nullsEmpty;
    protected $paginator;
    protected $columnator;
    protected $resp;
    protected $ok = false;

    public static function factory( $tableName, $extraArgs = [] ) {
        return new CRUD( $tableName, $extraArgs );
    }

    private static function header() {
        if ( self::$headerDisplayed ) {
            return '';
        }
        self::$headerDisplayed = true;
        $js = <<<JS
    var pageDirty = false;
    function toggleAll ( box, checkBoxTag, submitId ) {
        $("input[id^='" + checkBoxTag + "_']").attr ( 'checked', $(box).is(':checked') );
        showSubmit ( submitId );
    }
    function autoUpdate ( rowIdx, updTag, delTag, submitId ) {
        pageDirty = true;
        $('#' + updTag + '_' + rowIdx).attr('checked',true);
        $('#' + delTag + '_' + rowIdx).attr('checked',false);
        showSubmit ( submitId );
    }
    function showSubmit ( buttonId ) {
        $('#' + buttonId).fadeIn();
    }
    function checkIfADelete ( delTag ) {
        var numOfDeletes = 0;
        $("input[id^='" + delTag + "_']").each ( function () {
            if ( $(this).attr ( 'checked' ) ) numOfDeletes ++;
        });
        var plural = ( numOfDeletes == 1 ) ? '' : 's';
        var proceed = ( numOfDeletes > 0 ) ? confirm ( 'Process Row Delete' + plural + '?' ) : true;
        if ( proceed ) pageDirty = false;
        return proceed;
    }
    $().ready(function() {
        window.onbeforeunload = function () {
            if ( ! pageDirty ) return;
            return 'Changes have been made on this page and will not be saved.'
        };
    });
JS;
        return JS::library( JS::JQUERY ) .
                JS::javaScript( $js );
    }

    /**
     * Create the CRUD Object.
     * @param string $tableName The name of the table
     * @param array $extraArgs This is the properties that the CRUD will use to display/populate the database.
     * <pre>
     * $props = array ( 'primaryKey' => 'id', // Optional, if not supplied will be calculated.
     *                                        // Need to supply if the primary key is not simple column name
     *                  'db' => 'mydb',       // Optional, Name of the database. If not supplied defaults to DB::DEF
     *                                        // Database must be set up in the configuration
     *                  'where' => array ( 'pid' => 5 ),
     *                                        // Optional, List of conditions for the rows that we are looking for.
     *                                        // This would be used when looking for foreign key. These values will
     *                                        // be automatically inserted in new rows
     *                  'userCols' => array ( 'Mapping' => array ( $this, 'managePrivilegesCallBack' ) ),
     *                                        // This is a list of additional columns that will be added to the CRUD. These
     *                                        // will display the column using the title that you have suggested and
     *                                        // Then call the passed method.
     *                                        // call_user_func_array ( $col, array ( $idx, $row[$this->primaryKey] ) )
     *                                        // Passes back the row number and the primary key for this row
     *                                        // Then displays the html that the call back function generates
     *                  'canDelete' => true,  // Optional default: true. If you do not want user to delete rows set to false
     *                  'canUpdate' => true,  // Optional default: true. If you do not want user to update rows set to false
     *                  'canInsert' => true,  // Optional default: true. If you do not want user to insert rows set to false
     *                  'topPager' => true,   // Optional default: true. If you do not want pagination at top, set to false
     *                  'bottomPager' => true,// Optional default: true. If you do not want pagination at bottom, set to false
     *                  'suffix' => '_1',     // Optional default: current CRUD invocation number.
     *                                        // Useful if you have multiple CRUDs on one page. This is the suffix that
     *                                        // is attached to the form variables
     *                  'formAction' => 'view.php?ID=10',
     *                                        // Optional default to ?. On submirt this will return to the current page
     *                  'insDefaults' => array ( 'timestamp' => time() ),
     *                                        // Optional. If there are dfefaults that you wat inserted when the CRUD
     *                                        // inserts a row then you can list them here
     *                  'displayRows' =>10,   // Optional. Sets the number of rows that can be displayed
     *                  'nullsEmpty'  =>false,  // Optional. If this is true then it will put in nulls if the variable is empty
     *                  'dbType'      =>'mysql',// Optional. Tels the system if this is oracle, sqlite or mysql database
     *
     *                  Sort column
     *                  'colSort'      =>'fldStartTime',// Optional. Sets an initial sort column
     *                  'colSortOrder' =>'DESC',// Optional. Sets the direction of the sort column
     *                );
     * </pre>
     */
    public function __construct( $tableName, $extraArgs = [] ) {
        parent::__construct();
        $this->log = Log4PHP::logFactory( __CLASS__ );

        $this->tableName = $tableName;
        $this->primaryKey = ( isset( $extraArgs['primaryKey'] ) ) ? $extraArgs['primaryKey'] : null;
        $this->db = ( isset( $extraArgs['db'] ) ) ? $extraArgs['db'] : DB::DEF;
        $this->where = ( isset( $extraArgs['where'] ) ) ? $extraArgs['where'] : [];
        $this->extraCols = ( isset( $extraArgs['userCols'] ) ) ? $extraArgs['userCols'] : [];
        $this->canDelete = ( isset( $extraArgs['canDelete'] ) ) ? $extraArgs['canDelete'] : true;
        $this->canUpdate = ( isset( $extraArgs['canUpdate'] ) ) ? $extraArgs['canUpdate'] : true;
        $this->canInsert = ( isset( $extraArgs['canInsert'] ) ) ? $extraArgs['canInsert'] : true;
        $this->topPage = ( isset( $extraArgs['topPager'] ) ) ? $extraArgs['topPager'] : true;
        $this->bottomPage = ( isset( $extraArgs['bottomPager'] ) ) ? $extraArgs['bottomPager'] : true;
        $this->suffix = ( isset( $extraArgs['suffix'] ) ) ? $extraArgs['suffix'] : '_' . Invocation::next();
        $this->formAction = ( isset( $extraArgs['formAction'] ) ) ? $extraArgs['formAction'] : '?';
        $this->insDefaults = ( isset( $extraArgs['insDefaults'] ) ) ? $extraArgs['insDefaults'] : [];
        $this->nullsEmpty = ( isset( $extraArgs['nullsEmpty'] ) ) ? $extraArgs['nullsEmpty'] : false;
        $this->dbType = ( isset( $extraArgs['dbType'] ) ) ? $extraArgs['dbType'] : DB::driver( $this->db );

        $this->action = self::ACTION . $this->suffix;
        $this->delTag = 'D' . $this->suffix;
        $this->updTag = 'U' . $this->suffix;
        $this->gridTag = 'G' . $this->suffix;
        $this->submitId = 'S' . $this->suffix;

        $pageProps = [ 'suffix' => self::SUFFIX ];
        $this->paginator = new Paginator( $pageProps );

        $colProps = [ 'suffix' => self::SUFFIX ];
        if ( isset( $extraArgs['colSort'] ) ) {
            $colProps['init_column'] = $extraArgs['colSort'];
        }
        if ( isset( $extraArgs['colSortOrder'] ) ) {
            $colProps['init_order'] = $extraArgs['colSortOrder'];
        }

        $this->columnator = new Columnator( $colProps );

        $this->resp = new Response ();

        if ( isset( $extraArgs['displayRows'] ) ) {
            $this->paginator->setPageSize( $extraArgs['displayRows'] );
        }

        if ( !$this->getTableMetaData() ) {
            return;
        }

        $this->setupDefaultStyle();

        if ( $this->paginator->getRows() <= 0 ) {
            $this->paginator->setRows( $this->getRowCount() );
        }

        $this->copyVarsFromRequest( Columnator::navVar( self::SUFFIX ) );
        $this->copyVarsFromRequest( Paginator::navVar( self::SUFFIX ) );
        $this->copyVarsFromRequest( WebPage::ACTION );
        $this->ok = true;
    }

    public function index() {
        if ( !$this->ok ) {
            return 'Invalid table: ' . $this->tableName;
        }

        $html = $this->controller();

        $paginationHtml = $this->paginator->toHtml();

        $html .= Tag::form( [ 'action' => $this->formAction,
                    'onSubmit' => "if (!checkIfADelete('{$this->delTag}')) return false; return true;" ] ) .
                Tag::table( array_merge( [ 'id' => 'CRUD' . $this->suffix ], $this->styles[self::TABLE_C] ) ) .
                Tag::tr();
        if ( $this->canDelete ) {
            $js = "$().ready(function() { $('input[type=checkbox][name^={$this->delTag}]').shiftClick(); });";
            $html .= JS::library( 'jquery.shiftclick.js' ) .
                    JS::javaScript( $js ) .
                    Tag::th() .
                    Tag::hTag( 'span', [ 'title' => 'Click here to Toggle all the Delete checkboxes' ] ) . 'D' . Tag::_hTag( 'span' ) .
                    Tag::br() .
                    Tag::checkBox( '_dcheck', 'Y', false, [ 'onClick' => "toggleAll(this,'{$this->delTag}','{$this->submitId}')",
                        'title' => 'Toggle all the Delete checkboxes.' ] ) .
                    Tag::_th();
        }
        if ( $this->canUpdate ) {
            $js = "$().ready(function() { $('input[type=checkbox][name^={$this->updTag}]').shiftClick(); });";
            $html .= JS::library( 'jquery.shiftclick.js' ) .
                    JS::javaScript( $js ) .
                    Tag::th() .
                    Tag::hTag( 'span', [ 'title' => 'Click here to Toggle all the Update checkboxes' ] ) . 'U' . Tag::_hTag( 'span' ) .
                    Tag::br() .
                    Tag::checkBox( '_ucheck', 'Y', false, [ 'onClick' => "toggleAll(this,'{$this->updTag}','{$this->submitId}')",
                        'title' => 'Toggle all the Update checkboxes.' ] ) .
                    Tag::_th();
        }
        foreach ( $this->columnTitles as $colName => $title ) {
            if ( isset( $this->displayType[$colName] ) ) {
                if ( is_string( $this->displayType[$colName] ) ) {
                    $type = $this->displayType[$colName];
                }
                else {
                    $type = $this->displayType[$colName][0];
                }
            }
            else {
                $type = self::DISPLAY;
            }

            if ( !in_array( $type, [ self::HIDDEN, self::NONE ] ) ) {
                $html .= Tag::th() .
                        $this->columnator->toHtml( $colName, $title ) .
                        Tag::_th();
            }
        }

        foreach ( $this->extraCols as $title => $col ) {
            $html .= Tag::th();
            if ( isset( $this->columnTitles[$title] ) ) {
                $html .= $this->columnator->toHtml( $title, $this->columnTitles[$title] );
            }
            else {
                $html .= $title;
            }
            $html .= Tag::_th();
        }

        $html .= Tag::_tr() . "\n";

        $tab = $this->createSQLResult();
        $this->calculateColumnWidths( $tab );
        foreach ( $tab as $idx => $row ) {
            $html .= Tag::tr();
            if ( $this->canDelete ) {
                $html .= Tag::td( [ 'align' => 'center' ] ) .
                        Tag::checkBox( "{$this->delTag}[$idx]", $row[$this->primaryKey], false, [ 'id' => "{$this->delTag}_$idx",
                            'onClick' => "showSubmit('{$this->submitId}')",
                            'title' => 'Toggle to delete this row.' ] ) .
                        Tag::_td();
            }
            if ( $this->canUpdate ) {
                $html .= Tag::td( [ 'align' => 'center' ] ) .
                        Tag::checkBox( "{$this->updTag}[$idx]", $row[$this->primaryKey], false, [ 'id' => "{$this->updTag}_$idx",
                            'onClick' => "showSubmit('{$this->submitId}')",
                            'title' => 'Toggle to update this row.' ] ) .
                        Tag::_td();
            }
            foreach ( $row as $key => $value ) {
                $html .= $this->renderValue( $idx, $key, $value );
            }
            foreach ( $this->extraCols as $col ) {
                $html .= Tag::td() .
                        call_user_func_array( $col, [ $idx, $row[$this->primaryKey] ] ) .
                        Tag::_td();
            }
            $html .= Tag::_tr() . "\n";
        }

        $this->resp->set( $this->action, 'applyChanges' );

        $html .= Tag::_table() .
                $this->resp->toHidden() .
                Tag::submit( 'Apply Changes', [ 'style' => 'display: none',
                    'id' => $this->submitId,
                    'title' => 'Click here to apply the changes to this table' ] ) .
                Tag::_form();

        return self::header() .
                Widget::styleTable( '#CRUD' . $this->suffix ) .
                ( ( $this->topPage ) ? $paginationHtml : '' ) .
                $html .
                ( ( $this->bottomPage ) ? $paginationHtml : '' ) .
                $this->insertForm();
    }

    public function copyVarsFromRequest( $v ) {
        $this->resp->copyVarsFromRequest( $v );
        $this->paginator->getResponse()->copyVarsFromRequest( $v );
        $this->columnator->getResponse()->copyVarsFromRequest( $v );
        return $this;
    }

    /**
     * Sets up custom display for columns
     * @param string $colName
     * @param mixed $colStyle
     * @return CRUD current instance for chaining
     * <pre>
     * $crud->setColDisplay ( 'fldUserID',      array ( CRUD::SELECT, 'SELECT id,username FROM tblUser', $displayBlank ) )
     * $crud->setColDisplay ( 'fldGroupID',     array ( CRUD::SELECT, self::GROUP_SQL, true ) )
     * $crud->setColDisplay ( 'fldLevelID',     array ( CRUD::SELECT, array ( 1, 2, 3 ), true ) )
     * $crud->setColDisplay ( 'fldPrivilegeID',  CRUD::DISPLAY )
     * </./pre>
     */
    public function setColDisplay( $colName, $colStyle ) {
        $this->displayType[$colName] = $colStyle;
        return $this;
    }

    public function getTableName() {
        return $this->tableName;
    }

    public function setProperty( $name, $value ) {
        $this->$name = $value;
        return $this;
    }

    public function getProperty( $name ) {
        return $this->$name;
    }

    private function controller() {
        if ( ( $action = Request::get( $this->action ) ) == '' ) {
            return '';
        }
        if ( !method_exists( $this, $action ) ) {
            return '';
        }

        return $this->$action();
    }

    protected function applyChanges() {
        $grid = Request::get( $this->gridTag );
        $updateCnt = 0;
        $deleteCnt = 0;
        foreach ( Request::get( $this->updTag, [] ) as $idx => $id ) {
            $sql = 'UPDATE ' . $this->tableName . ' SET ';
            $params = [];
            foreach ( $grid[$idx] as $colName => $value ) {
                if ( $colName == $this->primaryKey ) {
                    continue;
                }
                if ( count( $params ) > 0 ) {
                    $sql .= ', ';
                }
                $sql .= $colName . '=?';

                switch ( $this->getColumnType( $colName ) ) {
                    case self::ENCTEXT:
                        $params[] = Cryptography::en( $value );
                        break;

                    case self::TIMESTAMP:
                        $params[] = strtotime( (int) $value );
                        break;

                    default:
                        if ( $this->nullsEmpty && empty( $value ) ) {
                            $value = null;
                        }
                        $params[] = $value;
                        break;
                }
            }

            $sql .= ' WHERE ' . $this->primaryKey . '=?';
            $params[] = $id;

            $updateCnt += $this->exec( $sql, $params );
        }

        foreach ( Request::get( $this->delTag, [] ) as $idx => $id ) {
            $sql = 'DELETE FROM ' . $this->tableName . ' WHERE ' . $this->primaryKey . '=?';
            $deleteCnt += $this->exec( $sql, $id );
        }

        if ( $deleteCnt > 0 ) {
            $this->paginator->setRows( $this->getRowCount() );
        }
        return 'Updated ' . $updateCnt . ', Deleted ' . $deleteCnt . ' rows' . Tag::br();
    }

    protected function insertRows() {
        $rowsToInsert = (int) Request::get( 'rows' );
        $insertedCnt = 0;
        for ( $i = 0; $i < $rowsToInsert; $i++ ) {
            $params = array_merge( $this->insDefaults, $this->where );
            $paramValues = null;

            if ( Cfg::get( 'jb_db', false )  && $this->db == DB::DEF ) {
                $params[$this->primaryKey] = DBMaintenance::dbNextNumber( $this->db, $this->tableName );
            }
            $sql = 'INSERT INTO ' . $this->tableName;
            if ( count( $params ) > 0 ) {
                $sql .= ' (' . join( ',', array_keys( $params ) ) . ') VALUES (' . DB::in( array_values( $params ), $paramValues ) . ')';
            }

            $insertedCnt += $this->exec( $sql, $paramValues );
        }

        if ( $insertedCnt > 0 ) {
            $this->paginator->setRows( $this->getRowCount() );
        }
        return 'Inserted ' . $insertedCnt . ' row' . StringUtil::plural( $insertedCnt ) . Tag::br();
    }

    private function renderValue( $rowIdx, $colName, $value ) {
        $html = '';
        $name = $this->gridTag . '[' . $rowIdx . '][' . $colName . ']';
        $autoUpdateJS = "autoUpdate($rowIdx,'{$this->updTag}','{$this->delTag}','{$this->submitId}');";
        $id = $this->gridTag . '_' . $rowIdx . '_' . $colName;

        $updClickAttrib = [ 'onClick' => $autoUpdateJS, 'id' => $id ];
        $updCheckAttrib = [ 'onChange' => $autoUpdateJS, 'id' => $id ];

        $type = $this->getColumnType( $colName );

        switch ( $type ) {
            case self::NONE:
                break;

            case self::DISPLAY:
                $html .= ( $value == '' ) ? '&nbsp;' : Tag::e( $value );
                break;

            case self::HIDDEN:
                $this->resp->set( $name, $value );
                break;

            case self::RADIO:
                $dispList = ( isset( $this->displayType[$colName][1] ) ) ? $this->displayType[$colName][1] : null;
                $updCheckAttrib['default'] = $value;
                $html .= Tag::table() .
                        Tag::tr() .
                        Tag::td( [ 'nowrap' => 'nowrap' ] ) .
                        implode( Tag::_td() . Tag::td( [ 'nowrap' => 'nowrap' ] ), Lists::radio( $name, $dispList, $updCheckAttrib ) ) .
                        Tag::_td() .
                        Tag::_tr() .
                        Tag::_table();
                break;

            case self::SELECT:
                $dispList = ( isset( $this->displayType[$colName][1] ) ) ? $this->displayType[$colName][1] : null;
                $blankLine = ( isset( $this->displayType[$colName][2] ) ) ? $this->displayType[$colName][2] : false;
                $updCheckAttrib['default'] = $value;
                $updCheckAttrib['hasBlank'] = $blankLine;
                $html .= Lists::select( $name, $dispList, $updCheckAttrib );
                break;

            case self::CHECKBOX:
                $checkValue = ( isset( $this->displayType[$colName][1] ) ) ? $this->displayType[$colName][1] : 'YES';
                $html .= Tag::checkBox( $name, $checkValue, $value == $checkValue, $updClickAttrib );
                break;

            case self::TIMESTAMP:
                $attribs = array_merge( $updCheckAttrib, $this->cellAttributes[$colName] );
                $attribs['value'] = date( 'Y-m-d H:i:s', (int) $value );
                $attribs['size'] = strlen( $attribs['value'] ) + 1;
                $html .= Tag::text( $name, $attribs );
                break;

            case self::ENCTEXT:
                $value = Cryptography::de( (string) $value );
            // Fall through to output text field

            case self::TEXT:
            default:
                $updCheckAttrib['value'] = (string) $value;
                $html .= Tag::text( $name, array_merge( $updCheckAttrib, $this->cellAttributes[$colName] ) );
                break;
        }

        if ( !in_array( $type, [ self::HIDDEN, self::NONE ] ) ) {
            $html = Tag::td() . $html . Tag::_td();
        }
        return $html;
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
        else if ( $colName == $this->primaryKey ) {
            $type = self::DISPLAY;
        }
        else {
            $type = self::TEXT;
        }

        if ( !$this->canUpdate && !in_array( $type, [ self::HIDDEN, self::NONE ] ) ) {
            $type = self::DISPLAY;
        }
        return $type;
    }

    private function insertForm() {
        if ( !$this->canInsert ) {
            return '';
        }

        $this->resp->set( $this->action, 'insertRows' );

        $html = Tag::form( [ 'action' => $this->formAction ] ) .
                Tag::text( 'rows', [ 'value' => '1', 'size' => '3' ] ) .
                $this->resp->toHidden() .
                Tag::submit( 'Insert' ) .
                Tag::_form();
        return $html;
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

    protected function getTableMetaData() {
        switch ( $this->dbType ) {
            case DB::MYSQL:
                $result = $this->query( 'DESCRIBE ' . $this->tableName );
                if ( !$result->ok() ) {
                    return false;
                }

                $keyColumn = $result->getColumn( 'Key' );
                $fieldColumn = $result->getColumn( 'Field' );
                $typeColumn = $result->getColumn( 'Type' );

                // Make sure that we have the primary key
                if ( $this->primaryKey == null ) {
                    $this->primaryKey = $fieldColumn[array_search( 'PRI', $keyColumn )];
                }

                // Get the column Titles
                foreach ( $fieldColumn as $col ) {
                    $this->columnTitles[$col] = $this->convertColumnToTitle( $col );
                    $this->cellAttributes[$col] = [];
                }

                // Get the column Titles
                foreach ( $typeColumn as $idx => $type ) {
                    if ( preg_match( '/^enum.*$/', $type ) ) {
                        $evalString = '$enumList=' . str_replace( 'enum', 'array', $type ) . ';';
                        eval( $evalString );
                        $this->setColDisplay( $fieldColumn[$idx], [ 'SELECT', $enumList ] );
                    }
                }

                return true;
            case DB::SQLITE:
                $result = $this->query( "PRAGMA table_info([{$this->tableName}])" );
                if ( !$result->ok() ) {
                    return false;
                }

                $keyColumn = $result->getColumn( 'pk' );
                $fieldColumn = $result->getColumn( 'name' );
                $typeColumn = $result->getColumn( 'type' );

                // Make sure that we have the primary key
                if ( $this->primaryKey == null ) {
                    $this->primaryKey = $fieldColumn[array_search( '1', $keyColumn )];
                }

                // Get the column Titles
                foreach ( $fieldColumn as $col ) {
                    $this->columnTitles[$col] = $this->convertColumnToTitle( $col );
                    $this->cellAttributes[$col] = [];
                }

                // Get the column Titles
                foreach ( $typeColumn as $idx => $type ) {
                    if ( preg_match( '/^enum.*$/', $type ) ) {
                        $evalString = '$enumList=' . str_replace( 'enum', 'array', $type ) . ';';
                        eval( $evalString );
                        $this->setColDisplay( $fieldColumn[$idx], [ 'SELECT', $enumList ] );
                    }
                }

                return true;
            case DB::ORACLE:
                $result = $this->query( 'SELECT * FROM user_tab_columns WHERE table_name=UPPER(?)', $this->tableName );
                if ( !$result->ok() ) {
                    return false;
                }
                $fieldColumn = $result->getColumn( 'COLUMN_NAME' );
                $typeColumn = $result->getColumn( 'DATA_TYPE' );

                // Make sure that we have the primary key
                if ( $this->primaryKey == null ) {
                    $this->primaryKey = $fieldColumn[0];
                }

                // Get the column Titles
                foreach ( $fieldColumn as $col ) {
                    $this->columnTitles[$col] = $this->convertColumnToTitle( $col );
                    $this->cellAttributes[$col] = [];
                }
                return true;
        }
        return false;
    }

    protected function createSQLResult() {
        $qry = $this->paginator->getLimits( $this->dbType, 'SELECT * FROM ' . $this->tableName . ' ' .
                $this->createSQLWhere( $params ) .
                $this->columnator->getSort() );
        $tab = $this->query( $qry, $params );

        //echo '<pre>createSQLResult: ' . $qry . "\n";
        //print_r ( $params );
        //echo '</pre>';

        return $tab;
    }

    private function calculateColumnWidths( &$tab ) {
        foreach ( $this->columnTitles as $colName => $title ) {
            if ( isset( $this->cellAttributes[$colName]['size'] ) ) {
                continue;
            }
            $width = $this->arrayMaxStringLength( $tab->getColumn( $colName ) );
            if ( $width > 40 ) {
                $width = 40;
            }
            if ( $width >= 0 && $width <= 40 ) {
                $this->cellAttributes[$colName]['size'] = $width;
            }
        }
    }

    private function arrayMaxStringLength( $arr ) {
        $maxWidth = 0;
        foreach ( $arr as $value ) {
            if ( ( $w = strlen( $value ) ) > $maxWidth ) {
                $maxWidth = $w;
            }
        }
        return $maxWidth;
    }

    private function createSQLWhere( &$params ) {
        $params = null;
        if ( count( $this->where ) <= 0 ) {
            return '';
        }

        $sql = ' WHERE ';
        $first = true;
        foreach ( $this->where as $key => $val ) {
            if ( !$first ) {
                $sql .= 'AND ';
            }
            $comp = ( stripos( $val, '%' ) === false ) ? '=' : ' like ';
            $sql .= $key . $comp . '?';
            $first = false;
        }
        $params = array_values( $this->where );

        //echo '<pre>createSQLWhere: ' . $qry . "\n";
        //print_r ( $params );
        //echo '</pre>';

        return $sql;
    }

    protected function convertColumnToTitle( $col ) {
        if ( $col == $this->primaryKey ) {
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

    public static function jbCol2Title( $col ) {
        $col = substr( $col, 3 );
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

    protected function getRowCount() {
        $qry = 'SELECT count(' . $this->primaryKey . ') FROM ' . $this->tableName . ' ' . $this->createSQLWhere( $params );
        return DB::oneValue( $this->db, $qry, $params );
    }

    protected function query( $qry, $params = null ) {
        //echo '<pre>query: ' . $qry . "\n";
        //print_r ( $params );
        //echo '</pre>';
        return new DBTable( $this->db, $qry, $params, DB::FETCH_ASSOC );
    }

    protected function exec( $qry, $params = null ) {
        //echo '<pre>' . $qry . "\n";
        //print_r ( $params );
        //echo '</pre>';
        return DB::exec( $this->db, $qry, $params );
    }
}
