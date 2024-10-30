<?php
namespace App\Controllers;

use \Jackbooted\Forms\Request;

class PartialDisplayController extends BaseController {
    const DEF       = '\App\Controllers\PartialDisplayController->index()';
    const ACTION    = '_PD_ACT';
    const SHORTCODE = 'yawpt-partial';

    public function __construct () {
        parent::__construct();
        $this->domainLead  = $this->getLeadDomain();
    }

    public function index() {
        $attr = Request::get( 'inject_atts' );
        if ( ! isset( $attr['files'] ) ) {
            return $this->errMsg( 'Need to include the attribute files with comma delimited list of templates' );
        }

        $html = '';
        foreach ( explode( ',', $attr['files'] ) as $templateFile ) {
            $html .= '<h2>' . $templateFile . '</h2>';
            $html .= $this->template( [], $templateFile );
            $html .= '<hr/>';
        }

        return $html;
    }
}
