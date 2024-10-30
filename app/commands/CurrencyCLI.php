<?php

namespace App\Commands;

class CurrencyCLI extends BaseCLI {
    public static $commands = [
        'CUR:initialize' => '\App\Commands\CurrencyCLI->initialize()',
        'CUR:update'     => '\App\Commands\CurrencyCLI->update()',
    ];
    public static $helpText = <<<TXT
        CUR:initialize      - Initializes the currency table to the default values
        CUR:update          - Updates the Currency conversion
        -------------------------------------------------------------------------------------
    TXT;

    public static function init() {
        parent::init();
    }

    public function update() {
        $results = new CLIResult();

        \App\Models\TblCurrencies::update();
        \App\Models\TblCurrencies::$currencyTableIDX = null;
        \App\Models\TblCurrencies::$currencyTableCode = null;
        \App\Models\TblCurrencies::loadCurrencies();
        $data = [];
        foreach ( \App\Models\TblCurrencies::$currencyTableIDX as $currency ) {
            $data[] = $currency->getData();
        }
        $results->success( $data );

        return $results->JSON();
    }

    public function initialize() {
        $results = new CLIResult();

        \App\Models\TblCurrencies::initialize();
        $results->success( 'The currency table has been initialized.' );
        $results->success( 'Recommend that you do an update to get the latest exchange rates' );
        $results->success( 'php jack.php CUR:update -vv' );

        return $results->JSON();
    }
}
