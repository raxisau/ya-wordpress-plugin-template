<?php

/** config.php - This file loads the various configuration options
 * *
 * * Written by Brett Dutton of Jackbooted Software
 * * brett@brettdutton.com
 * *
 * * This software is written and distributed under the GNU General Public
 * * License which means that its source code is freely-distributed and
 * * available to the general public.
 * *
 * */
// Create the $config array
$config = [];
if ( file_exists( __DIR__ . '/config.env.php' ) ) {
    require_once __DIR__ . '/config.env.php';
}

require_once $config['site_path'] . '/vendor/jackbooted/config/Cfg.php';
\Jackbooted\Config\Cfg::init( $config );
\Jackbooted\Config\Config::setOverrideScope( \Jackbooted\Config\Config::GLOBAL_SCOPE );
\Jackbooted\Config\Config::setHaveDB( $config['jb_db'] );
