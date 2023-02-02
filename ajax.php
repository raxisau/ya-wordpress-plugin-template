<?php
require_once __DIR__ . '/config.php';
if ( ( $html = \Jackbooted\Html\WebPage::controller() ) !== false ) {
    echo $html;
}