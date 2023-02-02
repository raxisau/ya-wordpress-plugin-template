<?php

namespace App\Controllers;

final class YAWPTController extends \Jackbooted\Util\JB {
    private static $instance = null;
    private static $allModels = [
        '\App\Models\MutexDAO',
    ];
    private static $deletableModels = [
    ];

    public $settings;

    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'ya-wordpress-plugin-template' ), YAWPT_VERSION );
    }

    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'ya-wordpress-plugin-template' ), YAWPT_VERSION );
    }

    public static function instance() {
        if ( self::$instance != null ) return self::$instance;

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
        add_action( 'plugins_loaded',        [ $this, 'loadTextdomain'     ] );
        add_action( 'init',                  [ $this, 'shortCodeInit'      ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueInit'        ] );
        add_action( 'wp_enqueue_scripts',    [ $this, 'enqueueInit'        ] );
        add_action( 'wp_footer',             [ $this, 'footerCustomerHtml' ] );

        register_activation_hook(   YAWPT_PLUGIN_FILE, [ $this,     'onActivation'   ] );
        register_deactivation_hook( YAWPT_PLUGIN_FILE, [ $this,     'onDeactivation' ] );
        register_uninstall_hook(    YAWPT_PLUGIN_FILE, [ __CLASS__, 'onUninstall'    ] ); // Needs to be static

        // This is placeholder code for an ajax implementation
        // It is not used in this plugin yet.
        add_action( 'wp_ajax_getInfo',        [ $this, 'ajaxGetInfo' ] );
        add_action( 'wp_ajax_nopriv_getInfo', [ $this, 'ajaxGetInfo' ] );
    }

    // See here for WP Ajax development https://honarsystems.com/ajax-wordpress-development/
    public function ajaxGetInfo() {
        $response = ['error' => false ]; 
        if ( $response['error'] ) {
            echo "Error occured, please try again";
        }
        else {
            echo "Success here - TODO with something else";
        }
        wp_die();
    }

    public function enqueueInit() {
        wp_enqueue_script( 'jquery' );
        
        wp_enqueue_script( 'bootstrap-js', '//cdn.usebootstrap.com/bootstrap/latest/js/bootstrap.min.js', [ 'jquery'] , true);
        wp_enqueue_style(  'bootstrap',    '//cdn.usebootstrap.com/bootstrap/latest/css/bootstrap.min.css' );

        $pluginUrl = plugins_url( YAWPT_SLUG );
		wp_enqueue_style(  YAWPT_SLUG . '-fa',     $pluginUrl . '/assets/fontawesome-all.min.css', null, YAWPT_VERSION );
		wp_enqueue_style(  YAWPT_SLUG . '-styles', $pluginUrl . '/assets/style.css', null, YAWPT_VERSION );
        wp_enqueue_script( YAWPT_SLUG . '-script', $pluginUrl . '/assets/custom.js' );
    }

    public function loadTextdomain() {
        load_plugin_textdomain( YAWPT_PLUGIN_NAME, FALSE, YAWPT_PLUGIN_DIR . '/languages/' );
    }

    public function onActivation() {
        if ( !current_user_can( 'activate_plugins' ) ) {
            return;
        }
        $plugin = \Jackbooted\Forms\Request::get( 'plugin' );
        check_admin_referer( "activate-plugin_{$plugin}" );

        foreach ( self::$allModels as $modelClassName ) {
            $model = new $modelClassName();
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
        if ( YAWPT_PLUGIN_NAME != WP_UNINSTALL_PLUGIN ) {
            return;
        }

        foreach ( self::$deletableModels as $modelClassName ) {
            $model = new $modelClassName();
            \Jackbooted\DB\DB::exec( $model->db, 'DROP TABLE ' . $model->tableName );
        }
    }

    public function shortCodeInit() {
        add_shortcode( 'dhu-countdown', [ $this, 'shortCodeCountdown' ] );
        add_shortcode( 'dhu-unsub',     [ $this, 'shortCodeUnsub' ] );
        add_shortcode( 'dhu-signup',    [ $this, 'shortCodeSignup' ] );
        add_shortcode( 'dhu-partials',  [ $this, 'shortPartialEdit' ] );
    }

    public function shortPartialEdit( $atts = [], $content = null, $tag = '' ) {
        return $this->shortCodeGeneric( \App\Controllers\PartialEditController::class, $atts, $content, $tag );
    }

    public function shortCodeUnsub( $atts = [], $content = null, $tag = '' ) {
        return $this->shortCodeGeneric( \App\Controllers\UnsubController::class, $atts, $content, $tag );
    }

    public function shortCodeSignup( $atts = [], $content = null, $tag = '' ) {
        return $this->shortCodeGeneric( \App\Controllers\SignupController::class, $atts, $content, $tag );
    }

    public function shortCodeCountdown( $atts = [], $content = null, $tag = '' ) {
        return $this->shortCodeGeneric( \App\Controllers\CountdownController::class, $atts, $content, $tag );
    }

    public function shortCodeGeneric( $clazz, $atts, $content, $tag ) {
        \Jackbooted\Forms\Request::set( 'inject_atts'   , $atts );
        \Jackbooted\Forms\Request::set( 'inject_content', $content );
        \Jackbooted\Forms\Request::set( 'inject_tag'    , $tag );
        if ( ( $html = $clazz::controller( $clazz::DEF, $clazz::ACTION ) ) !== false ) {
            return $html;
        }
        return 'ShortCode has produced no output: ' . $tag;
    }

    public function footerCustomerHtml() {
        $template = new \Jackbooted\Html\Template( YAWPT_PARTIALS . '/spinner.html', \Jackbooted\Html\Template::FILE );
        echo $template->replace( [ 'plugin_dir' => YAWPT_PLUGIN_URL ] )->toHtml();
    }

    public static function notifySupport( $subject, $msg ) {
        if ( ! is_string( $msg ) ) {
            $msg = json_encode( $msg, JSON_PRETTY_PRINT );
        }
        self::sendMail( $subject, $msg, 'it@b2bconsultancy.asia', 'IT Support'  );
    }

    public static function sendMail( $subject, $body, $to, $toName=''  ) {

        $emailTo = ( $toName == '' ) ? $to : "{$toName}<{$to}>";
        $headers = [
            'Date: ' . date( 'r' ),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ];

        $fullEmail = \App\Models\MailData::$emailHeader . $body . \App\Models\MailData::$emailFooter;

        $htmlFunc = [ __CLASS__, 'wpMailHtml' ];
        add_filter(    'wp_mail_content_type', $htmlFunc );
        $emailResult = wp_mail( $emailTo, $subject, $fullEmail, $headers );
        remove_filter( 'wp_mail_content_type', $htmlFunc );

        return $emailResult;
    }

    public static function wpMailHtml() {
        return 'text/html';
    }
}
