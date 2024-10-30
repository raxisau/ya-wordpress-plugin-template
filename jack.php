<?php
if ( ( $sapi = php_sapi_name() ) != 'cli' ) {
    echo "Can only run on command line: {$sapi}\n";
    exit;
}

if ( in_array( '-v', $argv ) || in_array( '-vv', $argv ) ) {
    error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );
    ini_set('display_errors', '1');
}
require_once dirname( dirname( dirname( __DIR__ ) ) ). '/wp-config.php';
require_once __DIR__ . '/config.php';
if ( ( $output = \Jackbooted\Html\WebPage::controller( \App\Commands\CLI::DEF ) ) !== false ) {
    echo $output;
}
