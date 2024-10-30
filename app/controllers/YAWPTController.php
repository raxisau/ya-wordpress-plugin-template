<?php

namespace App\Controllers;

final class YAWPTController extends \Jackbooted\Util\JB {
    private static $instance = null;
    private static $allModels = [
        '\App\Models\MutexDAO',
    ];
    private static $deletableModels = [
    ];
    private static $exemptScanClasses = [
        'BaseController',
        'YAWPTController',
        'YAWPTSettingsController',
    ];

    public static $shortCodeList = [];
    public static $ajaxEndpoints = [];

    public $settings;

    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'ya-wordpress-plugin-template' ), YAWPT_VERSION );
    }

    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'ya-wordpress-plugin-template' ), YAWPT_VERSION );
    }

    public static function instance() {
        if ( self::$instance != null ) {
            return self::$instance;
        }

        self::$instance = new YAWPTController();

        do_action( 'YAWPT/plugin_loaded' );
        return self::$instance;
    }

    function __construct() {
        parent::__construct();

        // Set up the Admin Stuff
        $this->settings = new YAWPTSettingsController();

        // Set up local shortcode wiring
        $this->setupHookWiring();
    }

    private function setupHookWiring() {
        add_action( 'plugins_loaded',        [ $this, 'loadTextdomain'   ] );
        add_action( 'init',                  [ $this, 'shortCodeInit'    ] );
        add_action( 'init',                  [ $this, 'ajaxEndpointInit' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueInit'      ] );
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueueInit'      ] );
        add_action( 'wp_body_open',          [ $this, 'bodyOpenHtml'     ] );

        register_activation_hook(   YAWPT_PLUGIN_FILE, [ $this,     'onActivation'   ] );
        register_deactivation_hook( YAWPT_PLUGIN_FILE, [ $this,     'onDeactivation' ] );
        register_uninstall_hook(    YAWPT_PLUGIN_FILE, [ __CLASS__, 'onUninstall'    ] ); // Needs to be static
    }

    public function enqueueInit() {
        wp_enqueue_script( 'jquery' );

        wp_enqueue_script( 'bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js', [ 'jquery'] , true);
        wp_enqueue_style(  'bootstrap',    '//cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' );

        $pluginUrl = plugins_url( YAWPT_SLUG );

		wp_enqueue_style(  YAWPT_SLUG . '-fa',     $pluginUrl . '/assets/fontawesome-all.min.css', null, YAWPT_VERSION );
		wp_enqueue_style(  YAWPT_SLUG . '-styles', $pluginUrl . '/assets/style.css',               null, YAWPT_VERSION );
        wp_enqueue_script( YAWPT_SLUG . '-script', $pluginUrl . '/assets/custom.js' );
    }

    public function loadTextdomain() {
        load_plugin_textdomain( YAWPT_SLUG, FALSE,  dirname( dirname ( __DIR__ ) ) . '/languages/' );
    }

    public function onActivation() {
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }
        $plugin = \Jackbooted\Forms\Request::get( 'plugin' );
        check_admin_referer( "activate-plugin_{$plugin}" );

        foreach ( self::$allModels as $modelClassName ) {
            $model = new $modelClassName();
            if ( $model->tableStructure == '' ) continue;
            \Jackbooted\DB\DB::exec( $model->db, $model->tableStructure );
        }
    }

    public function onDeactivation() {
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }
        $plugin = \Jackbooted\Forms\Request::get( 'plugin' );
        check_admin_referer( "deactivate-plugin_{$plugin}" );
    }

    public static function onUninstall() {
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }

        check_admin_referer( 'bulk-plugins' );

        // Important: Check if the file is the one that was registered during the uninstall hook.
        if ( YAWPT_SLUG != WP_UNINSTALL_PLUGIN ) {
            return;
        }

        foreach ( self::$deletableModels as $modelClassName ) {
            $model = new $modelClassName();
            \Jackbooted\DB\DB::exec( $model->db, 'DROP TABLE ' . $model->tableName );
        }
    }

    public function shortCodeInit() {
        $ctrlDir = __DIR__;
        $handle = opendir( $ctrlDir );
        while ( false !== ( $file = readdir( $handle ) ) ) {
            if ( strpos( $file, '.php' ) === false ) {
                continue;
            }

            $shortClassName = substr( $file, 0, -4 );
            if ( in_array( $shortClassName, self::$exemptScanClasses ) ) {
                continue;
            }

            $fullClassName = '\App\Controllers\\' . $shortClassName;
            if ( ! defined( $fullClassName . '::SHORTCODE' ) ) {
                continue;
            }

            self::$shortCodeList[$fullClassName::SHORTCODE] = $fullClassName;
            add_shortcode( $fullClassName::SHORTCODE, [ $this, 'shortCodeRunner' ] );
        }
        closedir( $handle );
    }

    public function ajaxEndpointInit() {
        $ajaxRunnerRef = [ $this, 'ajaxRunner' ];
        $ctrlDir = __DIR__;
        $handle = opendir( $ctrlDir );

        while ( false !== ( $file = readdir( $handle ) ) ) {
            if ( strpos( $file, '.php' ) === false ) {
                continue;
            }

            $shortClassName = substr( $file, 0, -4 );
            if ( in_array( $shortClassName, self::$exemptScanClasses ) ) {
                continue;
            }

            $fullClassName = '\App\Controllers\\' . $shortClassName;
            if ( ! isset( $fullClassName::$ajaxEndpoints ) ) {
                continue;
            }

            foreach ( $fullClassName::$ajaxEndpoints as $name => $endPoint ) {
                self::$ajaxEndpoints[$name] = $endPoint;
                add_action( 'wp_ajax_'        . $name, $ajaxRunnerRef );
                add_action( 'wp_ajax_nopriv_' . $name, $ajaxRunnerRef );
            }
        }
        closedir( $handle );
    }

    // See here for WP Ajax development https://honarsystems.com/ajax-wordpress-development/
    public function ajaxRunner() {
        $name = \Jackbooted\Forms\Request::get( 'action' );
        if ( isset( self::$ajaxEndpoints[$name] ) ) {
            $action = self::$ajaxEndpoints[$name];
            if ( ( $json = \Jackbooted\Html\WebPage::execAction( $action ) ) !== false ) {
                header( 'Content-type: application/json' );
                echo $json;
                wp_die();
            }
        }

        echo json_encode( false );
        wp_die();
    }

    public function shortCodeRunner( $atts, $content, $tag ) {
        if ( ! isset( self::$shortCodeList[$tag] ) ) {
            return 'ShortCode has no associated Class: ' . $tag;
        }

        \Jackbooted\Forms\Request::set( 'inject_atts'   , $atts );
        \Jackbooted\Forms\Request::set( 'inject_content', $content );
        \Jackbooted\Forms\Request::set( 'inject_tag',     $tag );

        $clazz = self::$shortCodeList[$tag];
        if ( ( $html = $clazz::controller( $clazz::DEF, $clazz::ACTION ) ) !== false ) {
            return $html;
        }
        return 'ShortCode has produced no output: ' . $tag;
    }

    public function bodyOpenHtml() {
        $template = new \Jackbooted\Html\Template( dirname( dirname( __DIR__ ) ) . '/partials/spinner.html', \Jackbooted\Html\Template::FILE );
        echo $template->replace( [ 'plugin_dir' => plugin_dir_url( dirname( __DIR__ ) ) ] )->toHtml();
    }

    public static function sendMail( $subject, $body, $to, $toName=''  ) {

        $emailTo = ( $toName == '' ) ? $to : "{$toName}<{$to}>";
        $headers = [
            'Date: ' . date( 'r' ),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ];

        $fullEmail = \App\Models\MailData::$header . $body . \App\Models\MailData::$footer;

        $htmlFunc = [ __CLASS__, 'wpMailHtml' ];
        add_filter( 'wp_mail_content_type', $htmlFunc );
        $emailResult = wp_mail( $emailTo, $subject, $fullEmail, $headers );
        remove_filter( 'wp_mail_content_type', $htmlFunc );

        return $emailResult;
    }
    public static function wpMailHtml() {
        return 'text/html';
    }
}
