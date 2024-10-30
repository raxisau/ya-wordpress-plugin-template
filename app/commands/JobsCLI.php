<?php
namespace App\Commands;

class JobsCLI extends BaseCLI {
    public static $commands = [
        'JOB:log'     => '\App\Commands\JobsCLI->logJob()',
    ];
    public static $helpText = <<<TXT
        JOB:log             - Logs a job to a database table. (Used in script for logging)
        -------------------------------------------------------------------------------------
    TXT;

    public static function init () {
        parent::init();
    }

    public function logJob() {
        if ( ( $fileName = self::arg( '-f' ) ) === false ) {
            return $this->JSON( [ 'error' => [ 'Missing -f fileName' ] ] );
        }

        $jobOutput = file_get_contents( $fileName );
        $regexp = '/<DB column=\"([a-z,_]*)\">(.*)<\/DB>/m';
        preg_match_all($regexp, $jobOutput, $matches, PREG_SET_ORDER, 0);

        $trimmedOutput = trim( preg_replace( $regexp, '', $jobOutput ) );
        $dbRow = [
            'job_output' => $trimmedOutput,
            'output_len' => strlen( $trimmedOutput )
        ];

        foreach ( $matches as $match ) {
            if ( isset( $match[1] ) && isset( $match[2] ) ) {
                $dbRow[$match[1]] = $match[2];
            }
        }

        if ( count($dbRow) == 6 ) {
            \App\Models\JobLog::factory($dbRow)->save();
        }

        return '';
    }
}
