<?php
namespace App\Commands;

class CronCLI extends BaseCLI {
    public static $commands = [
        'CRON:jobs' => '\App\Commands\CronCLI->jobs()',
        'CRON:run'  => '\App\Commands\CronCLI->run()',
    ];
    public static $helpText = <<<TXT
        CRON:jobs           - Displays the list of cronjobs
        CRON:run            - Set to run every minute to process jobs
        -------------------------------------------------------------------------------------
    TXT;

    public static function init () {
        parent::init();
    }

    public function jobs() {
        $results = new CLIResult();
        $results->success( \Jackbooted\Cron\Scheduler::jobs() );
        return $results->JSON();
    }

    public function run() {
        $results = new CLIResult();

        [ $processed, $msg ] = \Jackbooted\Cron\Cron::run( );
        $results->success( $msg );
        $results->processed( $processed );
        return $results->JSON();
    }
}
