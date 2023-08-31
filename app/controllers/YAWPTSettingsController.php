<?php
namespace App\Controllers;

final class YAWPTSettingsController extends \Jackbooted\Util\JB {
    const SLUG = 'yawpt';

    const APIKEY    = 'api_key_0';
    const APIURL    = 'api_url_1';
    const TWOCHANCE = 'two_chance_1';

    private $options = null; // Cached options
    private $fields  = null; // Set up in the constructor. What fields are we saving
    private $pluginName;

    private $slug           = self::SLUG;
    private $pageTitle      = 'Yet Another Wordpress Plugin Template Page Title';
    private $menuTitle      = 'YAWPT Menu Title';
    private $capability     = 'manage_options';

    private $optionName     = self::SLUG . '_option_name';
    private $optionGroup    = self::SLUG . '_option_group';
    private $settingSection = self::SLUG . '_setting_section';
    private $pageName       = self::SLUG . '-admin';

    private $textfields = false;

    // https://developer.wordpress.org/resource/dashicons
    private $helpIcon       = 'dashicons-media-document';

    private $menuList = [];

    function __construct() {
        parent::__construct();

        $this->pluginName = YAWPT_NAME;

        // These fields set up the options that we want in this plugin
        $this->fields = [
            self::APIKEY => [
                'title'    => 'API Key',
                'function' => function () {
                    $this->textInput( self::APIKEY, $this->apiKey() );
                }
            ],
            self::APIURL  => [
                'title'    => 'API URL',
                'function' => function () {
                    $this->textInput( self::APIURL, $this->apiUrl() );
                }
            ],
            self::TWOCHANCE => [
                'title' => 'Debug Mode',
                'function' => function () {
                    $this->checkboxInput( self::TWOCHANCE, $this->debugMode() );
                }
            ],
        ];

        $this->textfields = [
            self::APIKEY,
            self::APIURL,
        ];
        $this->checkfields = [
            self::TWOCHANCE,
        ];

        $this->menuList = [
            '_edt' => [
                'page_title' => 'Partial Editor',
                'menu_title' => 'Partial Editor',
                'callback'   => function () {
                    $this->genericBridge( 'Edit Partials', \App\Controllers\PartialEditController::class );
                },
            ],
            '_dbg' => [
                'page_title' => 'Debug Info',
                'menu_title' => 'Debug Info',
                'callback'   => function () {
                    $this->genericBridge( 'Debug Information', \App\Controllers\DebugController::class );
                },
            ],
        ];

        add_action( 'admin_menu', [ $this, 'addPluginPage' ] );
        add_action( 'admin_init', [ $this, 'pageInit' ] );
    }

    // This is for wordpress
    public function get_plugin_name() {
        return apply_filters( 'YAWPT/settings/get_plugin_name', $this->pluginName );
    }

    // ------------------------------------------------------------------
    // GETTERS - These functions will get the option that you are interested in
    public function apiKey() {
       $opts = $this->getOptions();
       return ( isset( $opts[self::APIKEY] ) ) ? $opts[self::APIKEY] : '';
    }
    public function apiUrl() {
        $opts = $this->getOptions();
        return ( isset( $opts[self::APIURL] ) ) ? $opts[self::APIURL] : '';
    }
    public function debugMode() {
        $opts = $this->getOptions();
        return ( isset( $opts[self::TWOCHANCE] ) ) ? $opts[self::TWOCHANCE] : 'NO';
    }
    // ------------------------------------------------------------------

    // ------------------------------------------------------------------
    // This is a utility function that will run a Jack controller in WordPress admin screen
    // generic function that will initiate a controller
    private function genericBridge( $title, $clazz ) {
        if ( ( $jackHtml = $clazz::controller( $clazz::DEF, $clazz::ACTION ) ) === false ) {
            $jackHtml = 'No output for this item - '. $title;
        }

        echo <<<HTML
            <div class="wrap">
                <h2>{$title}</h2>
                {$jackHtml}
                </form>
            </div>
HTML;
    }

    /////-----------------------------------------------------------------------------------
    ///// vvvv Below this line is all standard functions and should not need to change vvvvv
    /////-----------------------------------------------------------------------------------
    public function addPluginPage() {
        $configCallback = [ $this, 'createAdminPage' ];

        if ( count( $this->menuList ) == 0 ) {
            add_options_page( $this->pageTitle, $this->menuTitle, $this->capability, $this->slug, $configCallback );
        }
        else {
            add_menu_page( $this->pageTitle, $this->menuTitle, $this->capability, $this->slug, $configCallback, $this->helpIcon );
            foreach ( $this->menuList as $slug => $menu ) {
                add_submenu_page( $this->slug, $menu['page_title'], $menu['menu_title'], $this->capability, $this->slug . $slug, $menu['callback'] );
            }
        }
    }

    public function createAdminPage() {
        echo <<<HTML
            <div class="wrap">
                <h2>{$this->pageTitle}</h2>
                <p>This is the options that you will need to communicate with {$this->pageTitle} System</p>
HTML;
        settings_errors();
        echo   '<form method="post" action="options.php">';

        settings_fields( $this->optionGroup );
        do_settings_sections( $this->pageName );
        submit_button();

        echo <<<HTML
                </form>
            </div>
HTML;
    }

    public function pageInit() {
        register_setting( $this->optionGroup, $this->optionName, [ $this, 'sanitize' ] );
        add_settings_section( $this->settingSection, 'Settings', [ $this, 'sectionInfo' ], $this->pageName );
        foreach ( $this->fields as $key => $details ) {
            add_settings_field( $key, $details['title'], $details['function'], $this->pageName, $this->settingSection );
        }
    }

    public function sanitize( $input ) {
        $this->options = null;
        $sanitary_values = array();

        foreach ( $this->textfields as $key ) {
            if ( isset( $input[$key] ) ) {
                $sanitary_values[$key] = sanitize_text_field( $input[$key] );
            }
        }

        foreach ( $this->checkfields as $key ) {
            $sanitary_values[$key] = ( isset( $input[$key] ) &&  $input[$key] == 'YES' ) ? 'YES' : 'NO';
        }

        return $sanitary_values;
    }

    public function sectionInfo() {
    }

    private function textInput( $fieldName, $fieldValue ) {
        printf( '<input class="regular-text" type="text" name="%s[%s]" id="%s" value="%s">', $this->optionName, $fieldName, $fieldName, esc_attr( $fieldValue ) );
    }
    public function checkboxInput( $fieldName, $fieldValue ) {
        printf( '<input name="%s[%s]" type="checkbox" id="%s" value="YES" %s>', $this->optionName, $fieldName, $fieldName, ( $fieldValue == 'YES' ) ? 'checked' : '' );
    }

    public function getOptions() {
        if ( $this->options == null ) {
            $this->options = get_option( $this->optionName );
        }
        return $this->options;
    }
}