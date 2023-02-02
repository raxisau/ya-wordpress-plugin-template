<?php

namespace Jackbooted\Forms;

use \Jackbooted\Html\Lists;
use \Jackbooted\Html\Tag;
use \Jackbooted\Util\Invocation;
use \Jackbooted\Html\JS;
use \Jackbooted\DB\DB;

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
 */
class Paginator extends Navigator {

    const STARTING_PAGE     = 'G';
    const STARTING_ROW      = 'R';
    const TOTAL_ROWS        = 'T';
    const SQL_START         = 'Q';
    const ROWS_PER_PAGE     = 'P';
    const LOG_THRESHOLD     = 'L';
    const PAGE_VAR          = '_PG';
    const SUBMIT            = 'S';
    const PAGE_LINK_CLASS   = 'PAGE_LINK_CLASS';
    const PAGE_BUTTON_CLASS = 'PAGE_BUTTON_CLASS';

    const BUT_LAST  = '&nbsp;<i class="fas fa-fast-forward"></i>&nbsp;';
    const BUT_FIRST = '&nbsp;<i class="fas fa-fast-backward"></i>&nbsp;';
    const BUT_NEXT  = '&nbsp;<i class="fas fa-step-forward"></i></i>&nbsp;';
    const BUT_PREV  = '&nbsp;<i class="fas fa-step-backward"></i>&nbsp;';
    const BUT_STYLE = 'font: bold 11px Arial;text-decoration: none;background-color: #EEEEEE;color: #333333;padding: 2px 6px 2px 6px;border-top: 1px solid #CCCCCC;border-right: 1px solid #333333;border-bottom: 1px solid #333333;border-left: 1px solid #CCCCCC;';

    /**
     * @var integer Counts the number of times that this class is invoked so
     * that each invokation can have a unique id
     */
    private static $pagination = [
        self::STARTING_ROW  => 0,
        self::STARTING_PAGE => 0,
        self::TOTAL_ROWS    => 0,
        self::SQL_START     => 0,
        self::ROWS_PER_PAGE => 10
    ];
    private static $itemsPerPageList = [ 5, 10, 20, 50, 100, 200 ];

    /**
     * @static
     * @param  $suffix
     * @return string
     */
    public static function navVar( $suffix ) {
        return self::PAGE_VAR . $suffix;
    }

    private $dispPageSize;

    /**
     * Create a Pagination Object.
     * @param array $props This is the properties that the Paginator will use to display.
     * <pre>
     * $props = array ( 'attribs'          => 'array ( 'style' => 'display:none ), // Optional,
     *                                        // Attributes that will be stamped on the div that is generated
     *                                        // if not supplied will be empty array.
     *                                        // Need to supply if the primary key is not simple column name
     *                  'suffix'           => 'V', // Optional, suffix for the action variable for paginator
     *                                        // useful when there is a numbner on the screen
     *                                        // if not supplied one will be generated based on the number of
     *                                        // paginators that are generated
     *                  'request_vars'     => 'CEMID', // Optional, regexpression or individual name of any request
     *                                        //  vars that are to be copied to the response vars (chained vars)
     *                  'display_pagesize' => true, // Optional defaults to true. If false the page sizes will not
     *                                        // be displayed
     *                  'rows'             => 100,  // Optional. Number of rows that the Paginator has to deal with
     *                                        // Based on this number and the number of rows per page, the number of
     *                                        // pages are calculated
     *                  'def_num_rows'     => 15,  // Optional. Number of rows default on this pagination
     *                  'action'           => '?module=regadmin&action=index&',  // Optional. Overrides current form and URL actions
     *                );
     * </pre>
     */
    public function __construct( $props = [] ) {
        parent::__construct();

        $this->attribs      = ( isset( $props['attribs'] ) ) ? $props['attribs'] : [];
        $this->action       = ( isset( $props['action'] ) ) ? $props['action'] : '?';
        $suffix             = ( isset( $props['suffix'] ) ) ? $props['suffix'] : Invocation::next();
        $this->navVar       = self::navVar( $suffix );
        $initPattern        = ( isset( $props['request_vars'] ) ) ? $props['request_vars'] : '';
        $this->respVars     = new Response( $initPattern );
        $this->dispPageSize = ( isset( $props['display_pagesize'] ) ) ? $props['display_pagesize'] : true;

        $defPagination = array_merge( self::$pagination );
        if ( isset( $props['def_num_rows'] ) ) {
            $defPagination[self::ROWS_PER_PAGE] = $props['def_num_rows'];
        }
        if ( !in_array( $defPagination[self::ROWS_PER_PAGE], self::$itemsPerPageList ) ) {
            self::$itemsPerPageList[] = $defPagination[self::ROWS_PER_PAGE];
            sort( self::$itemsPerPageList );
        }

        // ensure that they have been set
        $requestPageVars = Request::get( $this->navVar, [] );
        foreach ( $defPagination as $key => $val ) {
            $this->set( $key, ( ( isset( $requestPageVars[$key] ) ) ? $requestPageVars[$key] : $val ) );
        }

        if ( isset( $props['rows'] ) ) {
            $this->setRows( (int) $props['rows'] );
        }

        $this->styles[self::PAGE_LINK_CLASS] = 'jb-pagelink';
        $this->styles[self::PAGE_BUTTON_CLASS] = 'jb-pagebuton';

        if ( $this->getStart() > 0 && $this->getRows() < $this->getPageSize() ) {
            $this->setStart( 0 );
        }
    }

    /**
     * @param  $rows
     * @return Navigator
     */
    public function setRows( $rows ) {
        return $this->set( self::TOTAL_ROWS, $rows );
    }

    /**
     * @return Response
     */
    public function getRows() {
        return $this->get( self::TOTAL_ROWS );
    }

    /**
     * @return Response
     */
    public function getStart() {
        return $this->get( self::STARTING_ROW );
    }

    /**
     * @param  $start
     * @return Navigator
     */
    public function setStart( $start ) {
        if ( $start > 0 && $this->getRows() < $this->getPageSize() ) {
            $start = 0;
        }

        return $this->set( self::STARTING_ROW, $start );
    }

    /**
     * @return Response
     */
    public function getPageSize() {
        return $this->get( self::ROWS_PER_PAGE );
    }

    /**
     * @param  $val
     * @return Navigator
     */
    public function setPageSize( $val ) {
        return $this->set( self::ROWS_PER_PAGE, $val );
    }

    /**
     * @param  $key
     * @param  $value
     * @return Paginator
     */
    public function setStyle( $key, $value ) {
        $this->styles[$key] = $value;
        $this->formVars[$key] = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getLimits( $dbType = DB::MYSQL, $sql = '' ) {
        $this->auditStartRow();

        if ( $dbType == DB::MYSQL || $dbType == DB::SQLITE ) {
            return $sql . ' LIMIT ' . $this->getStart() . ',' . $this->getPageSize();
        }
        else if ( $dbType == DB::ORACLE ) {
            $lowLim = $this->getStart();
            $upLim = $this->getStart() + $this->getPageSize();
            if ( $sql == '' ) {
                $sql = '%s';
            }
            $qry = <<<SQL
                SELECT * FROM (
                    SELECT t__.*,
                           ROWNUM AS r__
                    FROM (
                        $sql
                    ) t__
                )
                WHERE r__ BETWEEN $lowLim AND $upLim-1
SQL;
            return $qry;
        }
        else {
            return '';
        }
    }

    public function auditStartRow() {
        if ( $this->getStart() >= $this->getRows() ) {
            $this->setStart( 0 );
            $this->set( self::STARTING_PAGE, 0 );
        }
        return $this;
    }

    /**
     * @return string
     */
    public function toHtml() {
        $this->auditStartRow();

        $rowsPerPage = intval( $this->getPageSize() );
        $startingRow = intval( $this->getStart() );
        $startingPage = intval( $this->get( self::STARTING_PAGE ) );
        $totalRows = intval( $this->getRows() );
        $saveStartingRow = intval( $this->getStart() );

        if ( $rowsPerPage <= 0 ) {
            $rowsPerPage = self::$pagination[self::ROWS_PER_PAGE];
        }

        $actLen = strlen( $this->action );
        if ( substr( $this->action, $actLen - 1 ) == '&' ) {
            $action = substr( $this->action, 0, $actLen - 1 );
        }
        else {
            $action = $this->action;
        }

        // Not enough rows for pagination
        if ( $totalRows <= $rowsPerPage ) {
            if ( $this->dispPageSize && $totalRows > 10 ) {
                return Tag::div( $this->attribs ) .
                         Tag::form( [ 'method' => 'get', 'action' => $action ] ) .
                           $this->toHidden( [ self::ROWS_PER_PAGE ] ) .
                           '&nbsp;Max Rows:&nbsp;' .
                           Lists::select( $this->toFormName( self::ROWS_PER_PAGE ), self::$itemsPerPageList, [ 'default' => $rowsPerPage, 'onChange' => 'submit();' ] ) .
                         Tag::_form() .
                       Tag::_div();
            }
            else {
                return '';
            }
        }

        if ( $startingPage > 0 ) {
            $startingRow = ( $startingPage - 1 ) * $rowsPerPage;
            $this->set( self::STARTING_PAGE, 0 );
        }

        if ( $startingRow >= $totalRows ) {
            $startingRow = $totalRows - 1;
        }

        $pageContainingStartRow = intval( $startingRow / $rowsPerPage );
        $this->set( self::SQL_START, $rowsPerPage * $pageContainingStartRow );

        // Get number of pages
        $numberOfPages = intval( $totalRows / $rowsPerPage );
        if ( ( $totalRows % $rowsPerPage ) != 0 ) {
            $numberOfPages ++;
        }

        $previousPage = '';
        $nextPage = '';
        $firstPage = '';
        $lastPage = '';
        $pageSizeHtml = '';
        $html = [ [], [] ];

        // This is the navigation from the current page forward
        for ( $currentPage = $pageContainingStartRow + 1, $incr = 1; $currentPage < $numberOfPages - 1; $currentPage += $incr ) {
            $startingRowForThisPage = $currentPage * $rowsPerPage;
            $currentPageDisplay = $currentPage + 1;
            $this->set( self::STARTING_ROW, $startingRowForThisPage );
            $html[1][] = Tag::hRef( $this->toUrl(), number_format( $currentPageDisplay ), [ 'title' => 'Go to Page ' . $currentPageDisplay,
                                                                                            'style' => self::BUT_STYLE,
                                                                                            'class' => $this->styles[self::PAGE_LINK_CLASS] ] );
            $incr *= count( $html[1] );
        }

        // This is the navigation for next and last page
        if ( $pageContainingStartRow + 1 < $numberOfPages ) {
            $this->setStart( $rowsPerPage * ( $numberOfPages - 1 ) );
            $lastPage = Tag::hRef( '#', self::BUT_LAST, [ 'onclick' => "location.href='" . $this->toUrl() . "';return true;",
                                                          'title'   => 'Go to Last Page - ' . $numberOfPages,
                                                          'style'   => self::BUT_STYLE,
                                                          'class'   => $this->styles[self::PAGE_BUTTON_CLASS] ] );

            $this->setStart( $rowsPerPage * ( $pageContainingStartRow + 1 ) );
            $nextPage = Tag::hRef( '#', self::BUT_NEXT, [ 'onclick' => "location.href='" . $this->toUrl() . "';return true;",
                                                          'title'   => 'Go to Next Page - ' . ( $pageContainingStartRow + 2 ),
                                                          'style'   => self::BUT_STYLE,
                                                          'class'   => $this->styles[self::PAGE_BUTTON_CLASS] ] );
        }

        // Navigation for the current page nackwards
        for ( $currentPage = $pageContainingStartRow - 1, $incr = 1; $currentPage > 0; $currentPage -= $incr ) {
            $startingRowForThisPage = $currentPage * $rowsPerPage;
            $currentPageDisplay = $currentPage + 1;
            $this->setStart( $startingRowForThisPage );
            $html[0][] = Tag::hRef( $this->toUrl(), number_format( $currentPageDisplay ), [ 'title' => 'Go to Page ' . $currentPageDisplay,
                                                                                            'style' => self::BUT_STYLE,
                                                                                            'class' => $this->styles[self::PAGE_LINK_CLASS] ] );
            $incr *= count( $html[0] );
        }
        // Reverse the array so that it appears in correct order for pagination
        $html[0] = array_reverse( $html[0] );

        // Navigation for previous and first pages
        if ( $pageContainingStartRow != 0 ) {
            // Calculate navigation for first page
            $this->setStart( 0 );
            $firstPage = Tag::hRef( '#', self::BUT_FIRST, [ 'onclick' => "location.href='" . $this->toUrl() . "';return true;",
                                                            'title'   => 'Go to First Page - 1',
                                                            'style'   => self::BUT_STYLE,
                                                            'class'   => $this->styles[self::PAGE_BUTTON_CLASS] ] );

            // Calculate navigation for previous page
            $this->setStart( $rowsPerPage * ( $pageContainingStartRow - 1 ) );
            $previousPage = Tag::hRef( '#', self::BUT_PREV, [ 'onclick' => "location.href='" . $this->toUrl() . "';return true;",
                                                              'title'   => 'Go to Previous Page - ' . ( $pageContainingStartRow - 1 ),
                                                              'style'   => self::BUT_STYLE,
                                                              'class'   => $this->styles[self::PAGE_BUTTON_CLASS] ] );
        }


        $this->setStart( $saveStartingRow );
        $curPage = (string) ($pageContainingStartRow + 1);
        $exemptVars = [ self::STARTING_PAGE ];

        // Create the drop down to set the number of rows displayed per page
        if ( $this->dispPageSize ) {
            $exemptVars[] = self::ROWS_PER_PAGE;
            $pageSizeHtml = '&nbsp;Rows:&nbsp;' .
                    Lists::select( $this->toFormName( self::ROWS_PER_PAGE ), self::$itemsPerPageList, [ 'default'  => $rowsPerPage,
                                                                                                        'title'    => 'This changes the number of rows to display',
                                                                                                        'onChange' => 'submit();' ] );
        }

        return JS::library( 'fontawesome-all.min.css' ) .
               Tag::div( $this->attribs ) .
                 Tag::form( [ 'method' => 'POST', 'action' => $action ] ) .
                   $this->toHidden( $exemptVars ) .
                   $firstPage . '&nbsp;' .
                   $previousPage .
                   '&nbsp;' . join( '&nbsp;', $html[0] ) . '&nbsp;' .
                   Tag::text( $this->toFormName( self::STARTING_PAGE ), [ 'value' => $curPage,
                                                                          'align' => 'middle',
                                                                          'size' => 1 + max( 1, strlen( $curPage ) - 1 ),
                                                                          'title' => 'Manually enter the page number that you want and press enter',
                                                                          'style' => 'font-weight:bold;' ] ) .
                    '&nbsp;' . join( '&nbsp;', $html[1] ) . '&nbsp;' .
                    $nextPage .
                    '&nbsp;' . $lastPage .
                    $pageSizeHtml .
                  Tag::_form() .
                Tag::_div();
    }
}
