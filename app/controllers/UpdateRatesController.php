<?php
namespace App\Controllers;

use \Jackbooted\Html\Tag;

class UpdateRatesController extends BaseController {
    const DEF       = '\App\Controllers\UpdateRatesController->index()';
    const ACTION    = '_UR_ACT';
    const SHORTCODE = 'yawpt-rates';

    public function __construct () {
        parent::__construct();
        $this->domainLead  = $this->getLeadDomain();
    }

    public function index() {
        if ( ! $this->isAdmin ) {
            return '';
        }

        \App\Models\TblCurrencies::loadCurrencies();

        $html = '<h2>Rates Before</h2>' .
                Tag::div([ 'class' => 'container']) .
                  Tag::div([ 'class' => 'row']) .
                    Tag::div([ 'class' => 'col-6 border']) .
                      '<h3>Rate Name</h3>' .
                    Tag::_div() .
                    Tag::div([ 'class' => 'col-6 border']) .
                      '<h3>Rate Value</h3>' .
                    Tag::_div() .
                  Tag::_div();
        foreach ( \App\Models\TblCurrencies::$currencyTableIDX as $currency ) {
            $html .=
                  Tag::div([ 'class' => 'row']) .
                    Tag::div([ 'class' => 'col-6 border']) .
                      $currency->code .
                    Tag::_div() .
                    Tag::div([ 'class' => 'col-6 border']) .
                      $currency->rate .
                    Tag::_div() .
                  Tag::_div();
        }
        $html .=Tag::_div() .
                '<h2>Rates After</h2>' .
                Tag::div([ 'class' => 'container']) .
                  Tag::div([ 'class' => 'row']) .
                    Tag::div([ 'class' => 'col-6 border']) .
                      '<h3>Rate Name</h3>' .
                    Tag::_div() .
                    Tag::div([ 'class' => 'col-6 border']) .
                      '<h3>Rate Value</h3>' .
                    Tag::_div() .
                  Tag::_div();

        \App\Models\TblCurrencies::update();

        foreach ( \App\Models\TblCurrencies::$currencyTableIDX as $currency ) {
            $html .=
                  Tag::div([ 'class' => 'row']) .
                    Tag::div([ 'class' => 'col-6 border']) .
                      $currency->code .
                    Tag::_div() .
                    Tag::div([ 'class' => 'col-6 border']) .
                      $currency->rate .
                    Tag::_div() .
                  Tag::_div();
        }

        $html .=Tag::_div();

        return $html;
    }
}
