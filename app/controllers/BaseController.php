<?php
namespace App\Controllers;

use \Jackbooted\Forms\Request;

class BaseController extends \Jackbooted\Html\WebPage {
    const COMPLETED = 'COMPLETED';
    const EUR_SYM    = '&euro;';

    protected $curUser;
    protected $uid;
    protected $userEmail  = '';
    protected $nickName   = 'nobody';
    protected $isAdmin    = false;
    protected $isLoggedIn = false;
    protected $curURL;
    protected $settings;
    protected $partialDir;
    protected $pluginURL;
    protected $assetsURL;

    protected $apiClient;

    public function __construct () {
        parent::__construct();

        $user = wp_get_current_user();
        $this->curUser = $user;

        // WP returns a valid user object but the ID is 0 when user not logged in
        if ( ( $this->uid = $user->ID ) !== 0 ) {
            $this->userEmail  = $user->data->user_email;
            $this->nickName   = $user->data->display_name;

            // You can check for any other roles
            $this->isAdmin    = in_array( 'administrator', $user->roles );
            $this->isLoggedIn = true;
        }

        $this->settings   = YAWPTController::instance()->settings;

        $this->partialDir = dirname( dirname( __DIR__ ) ) . '/partials';
        $this->assetsURL  = plugin_dir_url( \Jackbooted\Config\Cfg::get( 'site_path' ) . '/1.php' ) . 'assets';

        $this->apiClient  = new \App\Libraries\IPGeolocationAPI( $this->settings->apiUrl(), $this->settings->apiKey() );

        if ( $this->settings->debugMode() ) {
            \App\App::debug();
            \App\Libraries\RestAPI::$debug = true;
        }
    }

    // Facade create a response object
    protected function response( $action ) {
        return \Jackbooted\Forms\Response::factory()
                                         ->action ( static::class . "->{$action}()", static::ACTION )
                                         ->copyVarsFromRequest( \Jackbooted\Forms\Paginator::PAGE_VAR_REGEX )
                                         ->copyVarsFromRequest( \Jackbooted\Forms\Columnator::COL_VAR_REGEX )
                                         ->copyVarsFromRequest( 'inject_atts' )
                                         ->copyVarsFromRequest( 'inject_content' )
                                         ->copyVarsFromRequest( 'inject_tag' )
                                         ->copyVarsFromRequest( 'fldRef' );
    }

    protected function checkLoggedIn() {
        if ( $this->isLoggedIn ) { return ''; }
        return $this->template( [], 'message_login.html' );
    }

    protected function errMsg( $val, $title='Error' ) {
        return $this->template( [ 'message' => $val, 'title' => $title ], 'message_error.html' );
    }

    protected function okMsg( $val, $title='All Ok' ) {
        return $this->template( [ 'message' => $val, 'title' => $title ], 'message_success.html' );
    }

    // FAcade over outputting template
    public function template( $data, $fileName ) {
        // This automatically puts this in the ajax URL into the tempate variables
        $data['ajaxUrl'] = admin_url( 'admin-ajax.php' );

        $fileName = $this->partialDir . '/' . $fileName;
        if ( ! file_exists( $fileName ) ) {
            return $this->errMsg( "Template file missing: {$fileName}" );
        }

        $template = new \Jackbooted\Html\Template( $fileName, \Jackbooted\Html\Template::FILE );
        return $template->replace( $data )->toHtml();
    }

    protected function checkMissingArgs( $expected ) {
        $missing = [];
        foreach ( $expected as $key => $friendlyName ) {
            if ( Request::get( $key ) == '' ) {
                $missing[] = sprintf( \App\ErrMsg::FORM_MISSING, $friendlyName );
            }
        }

        if ( count( $missing ) == 0 ) {
            return false;
        }

        return $missing;
    }
    protected function templateSystemErr( $errStr ) {
        return $errStr .
               $this->template( [], 'syserr.html' );
    }
    protected function getAction( ) {
        $action = $_SERVER['REQUEST_URI'];
        $actSep = ( strpos( $action, '?' ) === false ) ? '?' : '&';
        return [ $action, $actSep ];
    }
}
