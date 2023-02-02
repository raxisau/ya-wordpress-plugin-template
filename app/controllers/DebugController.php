<?php
namespace App\Controllers;

class DebugController extends BaseController {
    const DEF    = '\App\Controllers\DebugController->index()';
    const ACTION = '_DBG_ACT';

    public function __construct () {
        parent::__construct();
    }

    public function index() {
        $title = 'Debug Information';

        $debugInfo = '<table>';
        $debugInfo .= '<tr><td>DHU_NAME</td><td>'        . DHU_NAME . '</td></tr>';
        $debugInfo .= '<tr><td>DHU_VERSION</td><td>'     . DHU_VERSION . '</td></tr>';
        $debugInfo .= '<tr><td>DHU_PLUGIN_FILE</td><td>' . DHU_PLUGIN_FILE . '</td></tr>';
        $debugInfo .= '<tr><td>DHU_PLUGIN_BASE</td><td>' . DHU_PLUGIN_BASE . '</td></tr>';
        $debugInfo .= '<tr><td>DHU_PLUGIN_DIR</td><td>'  . DHU_PLUGIN_DIR . '</td></tr>';
        $debugInfo .= '<tr><td>DHU_PLUGIN_URL</td><td>'  . DHU_PLUGIN_URL . '</td></tr>';
        $debugInfo .= '<tr><td>DHU_PLUGIN_NAME</td><td>' . DHU_PLUGIN_NAME . '</td></tr>';
        $debugInfo .= '<tr><td>DHU_SLUG</td><td>'        . DHU_SLUG . '</td></tr>';
        $debugInfo .= '<tr><td>DHU_PRODREADY</td><td>'   . ( ( DHU_PRODREADY ) ? 'true' : 'false' ). '</td></tr>';
        $debugInfo .= '<tr><td>DHU_CURURL</td><td>'      . DHU_CURURL . '</td></tr>';
        $debugInfo .= '<tr><td>DHU_PARTIALS</td><td>'    . DHU_PARTIALS . '</td></tr>';
        $debugInfo .= '<tr><td>_SERVER</td><td><pre>'     . print_r( $_SERVER, true ) . '</pre></td></tr>';
        $debugInfo .= '<tr><td>_REQUEST</td><td><pre>'    . print_r( $_REQUEST, true ) . '</pre></td></tr>';
        $debugInfo .= '<tr><td>WP_User</td><td><pre>'     . print_r( wp_get_current_user(), true ) . '</pre></td></tr>';
        $debugInfo .= '</table>';

        $html = <<<HTML
            <div class="section-content relative">
              <div class="row">
                <div class="col medium-12 small-12 large-12">
                  <div class="col-inner">
                    {$debugInfo}
                  </div>
                </div>
              </div>
            </div>
HTML;
        return $html;

    }
}
