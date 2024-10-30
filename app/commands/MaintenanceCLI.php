<?php
namespace App\Commands;

class MaintenanceCLI extends BaseCLI {
    public static $commands = [
        'MAINT:db'         => '\App\Commands\MaintenanceCLI->db()',
        'MAINT:shortcodes' => '\App\Commands\MaintenanceCLI->shortcodes()',
        'MAINT:files'      => '\App\Commands\MaintenanceCLI->files()',
    ];
    public static $helpText = <<<TXT
        MAINT:db            - Run Some SQL for maintenance
        MAINT:shortcodes    - Display a list of Available shortcodes
        MAINT:files         - Runs maintenance on files. Log file deletion
        -------------------------------------------------------------------------------------
    TXT;

    public static function init () {
        parent::init();
    }

    public function files() {
        $results = new CLIResult();

        $fileList = [
            'Remove Old Log Files' => [
                'folder' => \Jackbooted\Util\PHPExt::getTempDir(),
                'regex'  => '/Log4PHP-log-([^\.]+)\.txt/m',
                'time'   => '-1 month',
            ],
        ];
        $match = [];

        foreach ( $fileList as $desc => $fileDetails ) {
            $results->debug( "{$desc} - Checking folder: {$fileDetails['folder']} for Files: {$fileDetails['regex']}, older than: {$fileDetails['time']}" );

            $exclDate = strtotime( $fileDetails['time'] );
            $exclDisp = \Jackbooted\Time\Stopwatch::dateToDB( $exclDate );
            $handle   = opendir( $fileDetails['folder'] );
            while ( false !== ( $file = readdir( $handle ) ) ) {
                $results->processed( );

                if ( ! preg_match( $fileDetails['regex'], $file, $match ) ) {
                    $results->debug( "Ignoring {$file} does not match the regex: {$fileDetails['regex']}" );
                    $results->processed( 1, 'ignore_count' );
                    continue;
                }

                if ( isset( $match[1] ) ) {
                    $logFileDate = strtotime( $match[1] );
                    if ( $logFileDate > $exclDate ) {
                        $results->debug( "Ignoring {$file} as date {$match[1]} is newer than {$exclDisp} " );
                        $results->processed( 1, 'ignore_count' );
                        continue;
                    }
                }

                $fullName = $fileDetails['folder'] . '/' . $file;
                if ( file_exists( $fullName ) ) {
                    $results->processed( 1, 'delete_count' );
                    $results->success( "Deleting file: {$fullName}" );
                    unlink( $fullName );
                }
            }
            closedir( $handle );
        }

        return $results->JSON();
    }

    public function db() {
        $results = new CLIResult();

        $cronDAO   = new \Jackbooted\Cron\CronDAO();
        $jobLogDAO = new \App\Models\JobLogDAO();
        $regLogDAO = new \App\Models\RegistrarLogDAO();

        $sqlList = [
            'Remove Old Cron'   => "DELETE FROM {$cronDAO->tableName}   WHERE fldRunTime<  DATE_ADD(NOW(), INTERVAL -5 DAY) AND fldStatus='COMPLETE'",
            'Remove Old JobLog' => "DELETE FROM {$jobLogDAO->tableName} WHERE fldStartTime<DATE_ADD(NOW(), INTERVAL -1 MONTH)",
            'Remove Old RegLog' => "DELETE FROM {$regLogDAO->tableName} WHERE fldStart<    DATE_ADD(NOW(), INTERVAL -1 MONTH)",
        ];

        foreach ( $sqlList as $desc => $sql ) {
            $results->debug( "About to run: [{$sql}]" );
            $processed = \Jackbooted\DB\DB::exec($cronDAO->db, $sql );
            $results->processed( $processed );
            $results->success( "Processed {$processed} for {$desc}" );
        }

        return $results->JSON();
    }

    public function shortcodes() {
        $results = new CLIResult();

        $settings = \App\Controllers\YAWPTController::instance();
        $results->success( $settings::$shortCodeList );

        return $results->JSON();
    }
}
