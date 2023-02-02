<?php
$config['version']      = 'JACKBOOTWEB Version 14.0';
$config['debug']        = ! isset ( $_SERVER['HTTP_HOST'] ) || strpos ( $_SERVER['HTTP_HOST'], 'local' ) !== false;
$config['cookie_path']  = '/';
$config['LF']           = "\r\n";

$config['site_path']    = __DIR__;
$config['tmp_path']     = $config['site_path'] . '/_private';
$config['class_path']   = [ 
    $config['site_path'] . '/vendor',
    $config['site_path'] . '/app',
    $config['site_path'] . '/partials',
];

$config['server']       = ( isset ( $_SERVER['HTTP_HOST'] ) ) ? $_SERVER['HTTP_HOST'] : 'cli.local';
$config['site_url']     = ( ( ! isset( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS'] != 'on' ) ? 'http://' : 'https://' ) . $config['server'];

$config['local-driver'] = 'sqlite';
$config['local-host']   = $config['tmp_path'] . '/jackbooted.sqlite';
$config['local-db']     = '';
$config['local-user']   = '';
$config['local-pass']   = '';

$config['wp-driver']    = 'mysql';
$config['wp-host']      = DB_HOST;
$config['wp-db']        = DB_NAME;
$config['wp-user']      = DB_USER;
$config['wp-pass']      = DB_PASSWORD;

$config['boss']         = 'brett@b2bconsultancy.asia';
$config['mail.smtp']    = 'b2bconsultancy.asia';
$config['desc']         = 'Yet Another Wordpress Plugin Template';
$config['title']        = 'Wordpress Plugin - Yet Another Wordpress Plugin Template';

$config['check_priviliages'] = false;  // If true checks all actions agains privilages tables
$config['encrypt_override']  = false;  // If this is set to true, the system does not do encryption
$config['maintenance']       = false;  // If this is set to true the system redirects to the maintenance.php page
$config['save_cookies']      = true;   // If true then the username, and password are saved in cookies user will have to login more often, but less secure
$config['jb_self_register']  = false;  // If true then guest user will be able to create account
$config['jb_forgery_check']  = false;  // If true system will check for URL and form variable tampering
$config['jb_tamper_detail']  = true;   // If true there will be more details about Tampering violations
$config['jb_audit_tables']   = false;  // If true all models will audit the tables to ensure they exist.
$config['jb_db']             = false;  // If this is standard Jackbooted database then the tables are of a vertain format

$config['timezone']          = 'UTC';
$config['known']             = [ 'plugin']; //TamperGuard Variables. Variables that add to Tamperguard that are not checked
$config['exempt']            = [ 'admin.php', 'plugins.php', 'ajax.php', 'cron.php', 'router.php', 'menu.php' ]; // List of files that are not checked
$config['crypto_location']   = 'config';   // If this is 'session' then it will look to the session variables, otherwise use the crypto_key
$config['crypto_key']        = 'xybqOIo1g1tHt0GHXS5mraNBt68gf6T7vdmTR8rE'; // This key is shuffled around and put into the session
                                
$config['modules'] = [ '\App\App' ];
$config['build_version'] = 'JackBooted Framework 2.1.1 (built: 2022-08-25 17:00:00)';
$config['tinymce_api'] = 'no-api-key';
