<?php
namespace App\Controllers;

use \Jackbooted\Html\Tag;

class GeoIPController extends BaseController {
    const DEF    = '\App\Controllers\GeoIPController->index()';
    const ACTION = '_GI_ACT';

    public function __construct () {
        parent::__construct();
    }

    public function index() {
        [ $action, $actSep ] = $this->getAction();
        $resp = $this->response( 'index' );
        $fldIP = \Jackbooted\Forms\Request::get( 'fldIP', $_SERVER['REMOTE_ADDR'] );

        $html = Tag::div([ 'class' => 'container']) .
                  Tag::div([ 'class' => 'row']) .
                    Tag::div([ 'class' => 'col-12 border']) .
                      '<h3>Enter IP</h3>' .
                      Tag::form( [ 'action' => $action ] ) .
                        $resp->toHidden() .
                        Tag::table( [ 'class' => 'table table-striped' ] ) .
                          Tag::tr() .
                            Tag::td( ) .
                              Tag::text( 'fldIP', $fldIP, )  .
                            Tag::_td() .
                          Tag::_tr() .
                          Tag::tr() .
                            Tag::td([ 'align' => 'center', 'colspan' => 3 ]) .
                              Tag::submit( 'Submit', 'Submit', ['class' => 'button primary btn btn-primary' ] ) .
                            Tag::_td() .
                          Tag::_tr() .
                        Tag::_table() .
                       Tag::_form() .
                       '<pre>' .
                           json_encode( $this->apiClient->singleIPGeoLookup( $fldIP ), JSON_PRETTY_PRINT ) .
                       '</pre>' .
                    Tag::_div() .
                  Tag::_div() .
                Tag::_div();
        return $html;
    }
}
