<?php

namespace Jackbooted\Cron;

use \Jackbooted\Forms\Request;
use \Jackbooted\Forms\Response;
use \Jackbooted\Html\JS;
use \Jackbooted\Html\Tag;
use \Jackbooted\Html\Validator;
use \Jackbooted\Html\WebPage;
use \Jackbooted\Html\Widget;

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
class SchedulerManager extends WebPage {
    const ACTION = '_JSM_ACT';
    const DEF = '\Jackbooted\Cron\SchedulerManager->index()';

    private $response;
    public  $action = '';
    public  $actSep = '?';

    public function __construct( $response=null ) {
        parent::__construct();
        $this->response = ( $response == null ) ? Response::factory() : $response;
    }

    public static function hello() {
        return [ 0, 'Hello World' ];
    }

    public function run( $default = '', $actionKey = self::ACTION ) {
        list ( $clazz, $rest ) = explode( '->', Request::get( $actionKey, $default ) );
        list ( $className, $functionName ) = self::normalizeCall( $clazz, $rest );
        return $this->$functionName();
    }

    public function index() {
        $rowCount = Scheduler::getRowCount();

        $pagNav = new \Jackbooted\Forms\Paginator(  [ 'suffix' => 'sm1', 'rows'        => $rowCount,  'def_num_rows' => 25 ] );
        $colNav = new \Jackbooted\Forms\Columnator( [ 'suffix' => 'sm1', 'init_column' => 'fldGroup', 'init_order' => 'ASC' ] );
        $pagNav->copyVarsFromRequest( \Jackbooted\Forms\Columnator::COL_VAR_REGEX );
        $colNav->copyVarsFromRequest( \Jackbooted\Forms\Paginator::PAGE_VAR_REGEX );

        foreach ( $this->response as $key => $val ) {
            $pagNav->getResponse()->set( $key, $val );
            $colNav->getResponse()->set( $key, $val );
        }

        foreach ( $this->response->getExempt() as $key ) {
            $pagNav->getResponse()->addExempt( $key );
            $colNav->getResponse()->addExempt( $key );
        }

        $this->response->copyVarsFromRequest( \Jackbooted\Forms\Columnator::COL_VAR_REGEX );
        $this->response->copyVarsFromRequest( \Jackbooted\Forms\Paginator::PAGE_VAR_REGEX );

        $schedulerList = Scheduler::displayList( $colNav->getSort(), $pagNav->getLimits() );
        $formName = 'SchedulerManager_index';
        $id = 'SchedulerManager_table';

        $jsUrl = \Jackbooted\Config\Cfg::get ( 'js_url') . '/images';
        $js = <<<JS
            jQuery().ready ( function () {
                jQuery('a.facebox').facebox({closeImage:'{$jsUrl}/closelabel.png', loadingImage:'{$jsUrl}/loading.gif' });
        JS;

        $valid = Validator::factory( $formName );

        $html = Tag::table( [ 'id' => $id ] ) .
                  Tag::tr() .
                    Tag::th() . 'Upd' .        Tag::_th() .
                    Tag::th( [ 'colspan' => 3 ] ) . '&nbsp;' .     Tag::_th() .
                    Tag::th() . '<i class="fas fa-check-square" title="Is this task active?"></i>' . Tag::_th() .
                    Tag::th() . $colNav->toHtml( 'fldGroup',       'Group' ) .       Tag::_th() .
                    Tag::th() . $colNav->toHtml( 'fldCommand',     'Command' ) .     Tag::_th() .
                    Tag::th() . $colNav->toHtml( 'fldDescription', 'Description' ) . Tag::_th() .
                    Tag::th() . $colNav->toHtml( 'fldStartTime',   'Start' ) .       Tag::_th() .
                    Tag::th() . $colNav->toHtml( 'fldCron',        'Cron' ) .        Tag::_th() .
                    Tag::th() . $colNav->toHtml( 'fldLastRun',     'Last Run' ) .    Tag::_th() .
                  Tag::_tr();

        if ( count( $schedulerList ) == 0 ) {
            $html .= Tag::tr() .
                       Tag::td( [ 'colspan' => 20 ] ) . 'No Scheduled Tasks' . Tag::_td() .
                     Tag::_tr();
        }
        else {
            $js .= <<<JS
                jQuery('input[type=checkbox][name^=fldUpd]').shiftClick();

            JS;
            $nw = [ 'nowrap' => 'nowrap' ];
            $rowIdx = 0;
            $histResp = Response::factory()->action( __CLASS__ . '->history()' );
            foreach ( $schedulerList as $idx => $schedulerItem ) {
                $row = '_' . $idx;

                $valid->addExists( 'fldCommand' . $row, 'Command must exist' )
                      ->addExists( 'fldCron' . $row, 'Interval must exist' );

                $js .= <<<JS
                    jQuery( '#fldStartDate$row' ).datetimepicker({
                        dateFormat: 'yy-mm-dd',
                        timeFormat: 'HH:mm'
                    });
                JS;
                $this->response->set( 'fldID', $idx );
                $lastRun = ( $schedulerItem->lastRun == '' ) ? '*Never*' : $schedulerItem->lastRun;
                $onChangeJS = "jQuery('#U$rowIdx').attr('checked',true)";
                $onChg = [ 'onChange' => $onChangeJS ];
                $delButton = Tag::href( $this->action . $this->actSep . $this->response->action( __CLASS__ . '->deleteItem()', self::ACTION )->toUrl(),
                                        '<i class="fas fa-trash-alt"></i>',
                                        [
                                            'onClick' => "confirm('Are you sure? This cannot be reversed, and you can just deactivate')",
                                            'title'   => 'Delete this entry',
                                            'class'   => 'btn btn-danger btn-xs',
                                            'style'   => 'color: white;',
                                        ] );
                $runButton = Tag::href(  $this->action . $this->actSep . $this->response->action( __CLASS__ . '->runItem()', self::ACTION )->toUrl(),
                                        '<i class="fas fa-running"></i>',
                                        [
                                            'onClick' => "confirm('Are you sure? This will run the job {$schedulerItem->cmd} now')",
                                            'class'   => 'btn btn-warning btn-xs',
                                            'title'   => "This will run the job {$schedulerItem->cmd} now",
                                            'style'   => 'color: white;',
                                        ] );

                $histButton = Tag::href(  '/ajax.php?' . $histResp->set( 'fldID', $idx )->toUrl(),
                                        '<i class="far fa-list-alt"></i>',
                                        [
                                            'class'   => 'btn btn-primary btn-xs facebox',
                                            'title'   => "Display the last executions for command: {$schedulerItem->cmd}",
                                            'style'   => 'color: white;',
                                        ] );
                $html .= Tag::tr() .
                           Tag::td() .
                             Tag::checkBox( 'fldUpd[]', $idx, false, [ 'id' => 'U' . $rowIdx ] ) .
                           Tag::_td() .
                           Tag::td() . $delButton  . Tag::_td() .
                           Tag::td() . $runButton  . Tag::_td() .
                           Tag::td() . $histButton . Tag::_td() .
                           Tag::td( [ 'align' => 'center' ] ) .
                             Tag::checkBox( 'fldActive' . $row, 'Yes', $schedulerItem->active == 'Yes', $onChg ) .
                           Tag::_td() .
                           Tag::td( $nw ) .
                             Tag::text( 'fldGroup' . $row, $schedulerItem->group,$onChg ) .
                           Tag::_td() .
                           Tag::td( $nw ) .
                             Tag::text( 'fldCommand' . $row, $schedulerItem->cmd, $onChg ) .
                           Tag::_td() .
                           Tag::td( [ 'width' => '100%' ] ) .
                             Tag::text( 'fldDescription' . $row, $schedulerItem->desc, [ 'style' => 'width:100%;', 'onChange' => $onChangeJS ] ) .
                           Tag::_td() .
                           Tag::td( $nw ) .
                             Tag::text( 'fldStartDate' . $row, $schedulerItem->start, [ 'id' => 'fldStartDate' . $row, 'size' => '10', 'onChange' => $onChangeJS ] ) .
                           Tag::_td() .
                           Tag::td( $nw ) .
                             Tag::text( 'fldCron' . $row, $schedulerItem->cron, [ 'size' => '15', 'onChange' => $onChangeJS ] ) .
                           Tag::_td() .
                           Tag::td( $nw ) . $lastRun . Tag::_td() .
                         Tag::_tr();
                $rowIdx ++;
            }
        }

        $html .= Tag::_table();
        $js .= '});';

        $formHtml = Tag::form( [ 'action' => $this->action, 'name' => $formName, 'onSubmit' => $valid->onSubmit() ] ) .
                      $html .
                      $this->response->action( __CLASS__ . '->save()', self::ACTION )->toHidden() .
                      Tag::submit( 'Save' ) .
                      Tag::linkButton(  $this->action . $this->actSep . $this->response->action( __CLASS__ . '->newItem()', self::ACTION ), 'New Item' ) .
                    Tag::_form();

        return JS::libraryWithDependancies( JS::JQUERY_UI_DATETIME ) .
               JS::library( 'jquery.shiftclick.js' ) .
               JS::libraryWithDependancies ( JS::FACEBOX ) .
               JS::javaScript( $js ) .
               $valid->toHtml() .
               Widget::styleTable( '#' . $id ) .
               $formHtml .
               $pagNav->toHtml();
    }

    public function history() {
        if ( ( $id = Request::get( 'fldID' ) ) == '' ) {
            return "Missing arg fldID";
        }

        if ( ( $schedItem = Scheduler::load( $id ) ) === false ) {
            return "Unable to load scheduler item: {$id}";
        }

        $sql = <<<SQL
            SELECT fldStartTime                                    AS `Start Time`,
                   TIMESTAMPDIFF(SECOND, fldStartTime, fldEndTime) AS `Run Time`,
                   fldExitCode                                     AS `Exit Code`,
                   fldJobOutput                                    AS Output
            FROM wp_jack_tbljoblog
            WHERE fldJobName=?
            ORDER BY fldJobLogID DESC
            LIMIT 50
        SQL;

        $tab = \Jackbooted\DB\DBTable::factory( \App\App::DB, $sql, [ $schedItem->cmd ], \Jackbooted\DB\DB::FETCH_ASSOC );
        if ( $this->getRowCount() == 0 ) {
            return "No history";
        }

        $html = Tag::table( [ 'class' => 'table table-striped', 'style' => 'border: 1px solid orange;' ] ) .
                  Tag::tr() .
                    Tag::th() . 'Start'  . Tag::_th() .
                    Tag::th() . 'Run'    . Tag::_th() .
                    Tag::th() . 'Exit'   . Tag::_th() .
                    Tag::th() . 'Output' . Tag::_th() .
                  Tag::_tr();

        $maxLen = 200;
        foreach ( $tab as $idx => $row ) {
            if ( preg_match( '/{.*}/ms', $row['Output'], $matches ) ) {
                $outputArr  = json_decode( $matches[0], JSON_OBJECT_AS_ARRAY );
                $outputJSON = json_encode( $outputArr, JSON_PRETTY_PRINT );

                if ( strlen( $outputJSON ) <= $maxLen ) {
                    $output = "<pre>{$outputJSON}</pre>";
                }
                else {
                    $outputJSONShort = substr( $outputJSON, 0, $maxLen );
                    $output = <<<HTML
                        <div id="sh_{$idx}" onclick="jQuery('#lg_{$idx}').show();jQuery('#sh_{$idx}').hide();">
                            <pre>{$outputJSONShort} ...</pre>
                        </div>
                        <div id="lg_{$idx}" onclick="jQuery('#sh_{$idx}').show();jQuery('#lg_{$idx}').hide();" style="display: none;">
                            <pre>{$outputJSON}</pre>
                        </div>
                    HTML;
                }
            }
            else {
                $output = "<pre>{$row['Output']}</pre>";
            }

            $html .=
                  Tag::tr() .
                    Tag::td( [ 'nowrap' => 'nowrap' ] ) . $row['Start Time'] . Tag::_td() .
                    Tag::td( [ 'nowrap' => 'nowrap' ] ) . $row['Run Time']   . Tag::_td() .
                    Tag::td( [ 'nowrap' => 'nowrap' ] ) . $row['Exit Code']  . Tag::_td() .
                    Tag::td( [ 'width' => '100%' ] )    . $output            . Tag::_td() .
                  Tag::_tr();
        }
        $html .= Tag::_table();

        return $html;
    }

    public function save() {
        $numChanged = 0;
        foreach ( Request::get( 'fldUpd', [] ) as $id ) {
            $sched = Scheduler::load( $id );
            $changed = false;
            if ( $sched->group != Request::get( 'fldGroup_' . $id ) ) {
                $sched->group = Request::get( 'fldGroup_' . $id );
                $changed = true;
            }
            if ( $sched->cmd != Request::get( 'fldCommand_' . $id ) ) {
                $sched->cmd = Request::get( 'fldCommand_' . $id );
                $changed = true;
            }
            if ( $sched->desc != Request::get( 'fldDescription_' . $id ) ) {
                $sched->desc = Request::get( 'fldDescription_' . $id );
                $changed = true;
            }
            if ( $sched->start != Request::get( 'fldStartDate_' . $id ) ) {
                $sched->start = Request::get( 'fldStartDate_' . $id );
                $changed = true;
            }
            if ( $sched->cron != Request::get( 'fldCron_' . $id ) ) {
                $sched->cron = Request::get( 'fldCron_' . $id );
                $changed = true;
            }

            if ( ( $active = Request::get( 'fldActive_' . $id ) ) != 'Yes' ) {
                $active = 'No';
            }
            if ( $sched->active != $active ) {
                $sched->active = $active;
                $changed = true;
            }

            if ( $changed ) {
                $sched->save();
                $numChanged ++;
            }
        }

        return Widget::popupWrapper( 'Saved Item(s) ' . $numChanged ) .
               $this->index();
    }

    public function runItem() {
        $sched = Scheduler::load( Request::get( 'fldID' ) );
        $data = [
            'ref'      => $sched->id,
            'cmd'      => $sched->cmd,
            'message'  => 'Manual',
            'priority' => 0,
        ];
        Cron::factory( $data )->save();
        $popUp = Widget::popupWrapper( "Executed command: {$sched->cmd}" );
        return $popUp . $this->index();
    }

    public function deleteItem() {
        if ( ( $sched = Scheduler::load( Request::get( 'fldID' ) ) ) !== false ) {
            $sched->delete();
            $popUp = Widget::popupWrapper( "Successfully deleted Job command: {$sched->cmd}, ID: {$sched->id}" );
        }
        else {
            $popUp = Widget::popupWrapper( "Job ID: {$sched->id} Not Found" );
        }
        return $popUp . $this->index();
    }

    public function newItem() {
        $defaults = [
            'cmd'     => __CLASS__ . '::hello();',
            'start'   => \Jackbooted\Time\Stopwatch::dateToDB(),
            'desc'    => 'This is a description',
            'cron'    => '* * * * *',
            'active'  => 'No',
            'lastRun' => ''
        ];
        Scheduler::factory( $defaults )->save();
        return Widget::popupWrapper( 'New Item Created' ) .
               $this->index();
    }
}
