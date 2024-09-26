<?php
namespace App\Controllers;

class CronController extends BaseController {
    const DEF       = '\App\Controllers\CronController->index()';
    const ACTION    = '_CRC_ACT';
    const SHORTCODE = 'ya-cron';

    public function __construct () {
        parent::__construct();
        $this->domainLead  = $this->getLeadDomain();
    }

    public function index() {
        if ( ! $this->isAdmin ) {
            return '';
        }

        $ctrl = new \Jackbooted\Cron\SchedulerManager( $this->response( 'index' ) );
        [ $ctrl->action, $ctrl->actSep ] = $this->getAction();
        $schedHtml = $ctrl->run( \Jackbooted\Cron\SchedulerManager::DEF );

        $html = <<<HTML
            <div class="row">
                <div class="col-md-10">
                    <h4>Schedule Manager</h4>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <pre>
        # WA    00 01 02 03 04 05 06 07 08 09 10 11 12 13 14 15 16 17 18 19 20 21 22 23
        # AEDT  03 04 05 06 07 08 09 10 11 12 13 14 15 16 17 18 19 20 21 22 23 00 01 02 (SYD/DST)
        # AEST  02 03 04 05 06 07 08 09 10 11 12 13 14 15 16 17 18 19 20 21 22 23 00 01 (BNE)
        # CAM   23 00 01 02 03 04 05 06 07 08 09 10 11 12 13 14 15 16 17 18 19 20 21 22
        # PST   09 10 11 12 13 14 15 16 17 04 19 20 21 22 23 00 01 02 03 04 05 06 07 08  (Vancouver)
        # EDT   12 13 14 15 16 17 04 19 20 21 22 23 00 01 02 03 04 05 06 07 08 09 10 11  (Toronto)
        # UTC   16 17 18 19 20 21 22 23 00 01 02 03 04 05 06 07 08 09 10 11 12 13 14 15
                    </pre>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    {$schedHtml}
                </div>
            </div>
        HTML;

        return $html;
    }
}
