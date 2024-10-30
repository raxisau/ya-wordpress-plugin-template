<?php
namespace App\Controllers;

class DebugController extends BaseController {
    const DEF       = '\App\Controllers\DebugController->index()';
    const ACTION    = '_DBG_ACT';
    const SHORTCODE = 'ya-debug';

    public function __construct () {
        parent::__construct();
    }

    public function index() {
        $debugInfo = '<table>';
        $debugInfo .= '<tr><td>YAWPT_NAME</td><td>'        . YAWPT_NAME . '</td></tr>';
        $debugInfo .= '<tr><td>YAWPT_VERSION</td><td>'     . YAWPT_VERSION . '</td></tr>';
        $debugInfo .= '<tr><td>YAWPT_PLUGIN_FILE</td><td>' . YAWPT_PLUGIN_FILE . '</td></tr>';
        $debugInfo .= '<tr><td>YAWPT_SLUG</td><td>'        . YAWPT_SLUG . '</td></tr>';

        $debugInfo .= '<tr><td>Cfg[site_path]</td><td>'  . \Jackbooted\Config\Cfg::get( 'site_path' ) . '</td></tr>';

        $debugInfo .= '<tr><td>$this->partialDir</td><td>' . $this->partialDir . '</td></tr>';
        $debugInfo .= '<tr><td>$this->assetsURL</td><td>'  . $this->assetsURL . '</td></tr>';

        $debugInfo .= '<tr><td>_SERVER</td><td><pre>'    . print_r( $_SERVER, true ) . '</pre></td></tr>';
        $debugInfo .= '<tr><td>_REQUEST</td><td><pre>'   . print_r( $_REQUEST, true ) . '</pre></td></tr>';
        $debugInfo .= '<tr><td>WP_User</td><td><pre>'    . print_r( wp_get_current_user(), true ) . '</pre></td></tr>';
        $debugInfo .= '</table>';

        $tempHtml = "Reserved section for template debug";

        $html = <<<HTML
            <div class="section-content relative">
              <div class="row">
                <div class="col medium-12 small-12 large-12">
                  <div class="col-inner">
                    {$debugInfo}
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col medium-12 small-12 large-12">
                  <div class="col-inner">
                    {$tempHtml}
                  </div>
                </div>
              </div>
            </div>
        HTML;
        return $html;

    }
}
