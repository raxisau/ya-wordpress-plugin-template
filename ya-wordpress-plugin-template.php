<?php
/**
 * Yet Another Wordpress Plugin Template
 *
 * @package       YAWPT
 * @author        Brett Dutton
 * @version       1.0.8
 *
 * @wordpress-plugin
 * Plugin Name:   Yet Another Wordpress Plugin Template
 * Plugin URI:    https://brettdutton.com
 * Description:   Shortcodes that help with any product that you see fit to run
 * Version:       1.0.8
 * Author:        Brett Dutton
 * Author URI:    https://b2bconsultancy.asia
 * Text Domain:   ya-wordpress-plugin-template
 * Domain Path:   /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
require_once __DIR__ . '/config.php';

define( 'YAWPT_NAME',        'Yet Another Wordpress Plugin Template' );
define( 'YAWPT_VERSION',     '1.0.8' );
define( 'YAWPT_PLUGIN_FILE', __FILE__ );
define( 'YAWPT_SLUG',        basename( __FILE__, '.php' ) );

function YAWPT() {
	return \App\Controllers\YAWPTController::instance();
}

YAWPT();
