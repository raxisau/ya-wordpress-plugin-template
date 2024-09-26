<?php
namespace App\Controllers;

use \Jackbooted\Html\Tag;

class GeoIPController extends BaseController {
    const DEF       = '\App\Controllers\GeoIPController->index()';
    const ACTION    = '_GI_ACT';
    const SHORTCODE = 'ya-geoip';

    public function __construct () {
        parent::__construct();
    }

    public function index() {
        [ $action, $actSep ] = $this->getAction();
        $resp = $this->response( 'index' );
        if ( ( $fldIP = \Jackbooted\Forms\Request::get( 'fldIP' ) ) != '' ) {
            $response = $this->apiClient->singleIPGeoLookup( $fldIP );
            if ( $response['error'] ) {
                $message = $response['msg'];
            }
            else {
                $message = json_encode( $response['result'], JSON_PRETTY_PRINT );
            }
        }
        else {
            $fldIP = $this->getUserIP();
            $message = '';
        }

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
                       Tag::pre() .
                         $message .
                       Tag::_pre() .
                    Tag::_div() .
                  Tag::_div() .
                Tag::_div();
        return $html;
    }

    private function getUserIP () {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        }
        elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        }
        elseif (getenv('HTTP_X_FORWARDED')) {
            $ip = getenv('HTTP_X_FORWARDED');
        }
        elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ip = getenv('HTTP_FORWARDED_FOR');
        }
        elseif (getenv('HTTP_FORWARDED')) {
            $ip = getenv('HTTP_FORWARDED');
        }
        else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}
