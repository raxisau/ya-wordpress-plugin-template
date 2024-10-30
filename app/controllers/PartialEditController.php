<?php
namespace App\Controllers;

use \Jackbooted\Html\Tag;
use \Jackbooted\Html\Lists;
use \Jackbooted\Forms\Request;

class PartialEditController extends BaseController {
    const DEF       = '\App\Controllers\PartialEditController->index()';
    const ACTION    = '_PE_ACT';
    const SHORTCODE = 'yawpt-partials';

    public function __construct () {
        parent::__construct();
        $this->domainLead  = $this->getLeadDomain();
    }

    public function index() {
        if ( ! $this->isAdmin ) {
            return '';
        }

        [ $action, $actSep ] = $this->getAction();

        $respList = $this->response( 'index' );
        $partialList = $this->getPartialList();
        if ( ( $partialFile = Request::get( 'fldPartial' ) ) == '' ) {
            $partialFile = $partialList[0];
        }
        $resp = $this->response( 'editPartial' );
        $resp->set( 'fldPartial', $partialFile );
        $size = count( $partialList );

        $html = Tag::div([ 'class' => 'container']) .
                  Tag::div([ 'class' => 'row']) .
                    Tag::div([ 'class' => 'col-3 border']) .
                      '<h3>Select partial</h3>' .
                      Tag::form( [ 'action' => $action ] ) .
                        $respList->toHidden() .
                        Lists::select( 'fldPartial', $partialList, [ 'style' => 'height: 100%', 'class' => 'form-select', 'aria-label' => "size {$size}", 'default' => $partialFile, 'size' => $size, 'onClick' => 'submit();' ] ) .
                      Tag::_form() .
                      Tag::form( [ 'action' => $action ] ) .
                        $resp->toHidden() .
                        Tag::submit( 'Edit', 'Edit', [ 'class' => 'button secondary btn btn-secondary' ] ) .
                      Tag::_form() .
                    Tag::_div() .
                    Tag::div([ 'class' => 'col-9 border']) .
                        $this->template( [], $partialFile ) .
                    Tag::_div() .
                  Tag::_div() .
                Tag::_div();

        return $html;
    }

    public function editPartial() {
        if ( ! $this->isAdmin ) {
            return '';
        }

        [ $action, $actSep ] = $this->getAction();

        $partialFile = Request::get( 'fldPartial' );
        $contents = htmlentities( $this->template( [], $partialFile ) );

        $respSave = $this->response( 'savePartial' );
        $respSave->set( 'fldPartial', $partialFile );

        $respCancel = $this->response( 'index' );
        $respCancel->set( 'fldPartial', $partialFile );

        $html = Tag::form( [ 'action' => $action ] ) .
                     $respSave->toHidden() .
                     Tag::table( [ 'class' => 'table table-striped' ] ) .
                       Tag::tr() .
                         Tag::td() .
                            "<h3>Edit the code below for the partial: {$partialFile}</h3>" .
                         Tag::_td() .
                       Tag::_tr() .
                       Tag::tr() .
                         Tag::td( ) .
                           Tag::textArea( 'fldPartialCode', $contents, [ 'id'    => 'fldContent',
                                                                         'style' => 'overflow-x: scroll;overflow-wrap: normal;white-space: pre;height:700px;width:100%; font-family:Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace;',
                                                                         'title' => 'Edit this partial' ] )  .
                         Tag::_td() .
                       Tag::_tr() .
                       Tag::tr() .
                         Tag::td([ 'align' => 'center', 'colspan' => 3 ]) .
                           Tag::submit( 'Save', 'Save', ['class' => 'button primary btn btn-primary' ] ) .
                           Tag::hRef( $action . $actSep . $respCancel->toURL(), 'Cancel', [ 'class' => 'button secondary btn btn-secondary' ] ) .
                         Tag::_td() .
                       Tag::_tr() .
                     Tag::_table() .
                    Tag::_form();
        return $html;
    }

    public function savePartial() {
        if ( ! $this->isAdmin ) {
            return '';
        }

        $partialFile = Request::get( 'fldPartial' );
        $partialCode = preg_replace('/\x0D\x0A/', "\n", stripslashes( Request::get( 'fldPartialCode' ) ) );
        $fileName = $this->partialDir . '/' . $partialFile;

        $origFileContents = file_get_contents( $fileName );
        $header = '';
        $footer = '';

        if ( preg_match( "/^.*<body[^>]*>/sim", $origFileContents, $matches ) ) {
            $header = $matches[0] . "\n";
        }
        if ( preg_match( "/<\/body>.*$/sim", $origFileContents, $matches ) ) {
            $footer = $matches[0];
        }

        file_put_contents( $fileName, $header . $partialCode . $footer );

        return $this->okMsg( "Sucessfully saved Partial: {$partialFile}" ) .
               $this->index();
    }

    private function getPartialList() {
        $handle = opendir( $this->partialDir );
        $partialList = [ ];
        while ( false !== ( $file = readdir( $handle ) ) ) {
            if ( $file == 'DomainHelpTemplate.html' ||
                 $file == 'OZRegistryTemplate.html' ||
                 strpos( $file, '.html' ) === false ) {
                continue;
            }
            $partialList[] = $file;
        }
        closedir( $handle );
        $partialList[] = 'ErrMsg.php';
        sort( $partialList, SORT_FLAG_CASE );
        return $partialList;
    }
}
