<?php

namespace App\Commands;

use \Jackbooted\DB\DB;

class InstallationCLI extends BaseCLI {
    public static $commands = [
        'DB:initialize'  => '\App\Commands\InstallationCLI->initialize()',
        'DB:model'       => '\App\Commands\InstallationCLI->model()',
        'DB:models'      => '\App\Commands\InstallationCLI->models()',
        'DB:tables'      => '\App\Commands\InstallationCLI->tables()',
    ];
    public static $helpText = <<<TXT
        DB:initialize       - Set up the base database
        DB:model            - Set up single model (php jack.php DB:model -vv -table  ModelName)
        DB:models           - Displays the list of Models
        DB:tables           - Displays the list of tables
        -------------------------------------------------------------------------------------
    TXT;

    private static $exemptModels = [
        'LocationList',
        'MailData',
    ];

    public static function init() {
        parent::init();
    }

    public function tables() {
        $results = new CLIResult();

        foreach( \Jackbooted\DB\DBTable::factory( \App\App::DB, 'SHOW TABLES', [], \Jackbooted\DB\DB::FETCH_NUM ) as $row ) {
            $results->success( $row[0] );
        }

        return $results->JSON();
    }

    public function models() {
        $results = new CLIResult();

        $results->debug( 'Scanning for all database models' );
        $allInit = '';
        foreach( self::getModelList() as $modelName ) {
            $cmd = "php jack.php DB:model -vv -table {$modelName}";
            $results->success( $cmd );
            $allInit .= $cmd . '; ';
        }
        $results->success( $allInit );

        return $results->JSON();
    }

    public function initialize() {
        $results = new CLIResult();

        $results->debug( "Scanning for all database models" );
        foreach( self::getModelList() as $modelName ) {
            self::createTable( $modelName, $results );
        }

        return $results->JSON();
    }

    public function model() {
        $results = new CLIResult();

        if ( ( $model = self::arg( '-table' ) ) === false ) {
            $results->error( "No -table declared" );
            return $results->JSON();
        }

        self::createTable( $model, $results );

        return $results->JSON();
    }
    private static function createTable( $model, $results ) {
        if ( in_array( $model, [ 'Cron', 'Scheduler' ] ) ) {
            $fullClassName = "\\Jackbooted\\Cron\\{$model}DAO";
        }
        else {
            $fullClassName = "\\App\\Models\\{$model}DAO";
        }

        $dao = new $fullClassName();
        if ( is_object( $dao ) && $dao->tableStructure != '' ) {
            $results->success( "creating Table - {$dao->tableName}" );
            DB::exec( $dao->db, $dao->tableStructure ) ;
        }
        else {
            $results->error( "Skipping $fullClassName as not Object" );
        }
    }
    private static function getModelList() {

        // The list to include the Jackbooted models
        $modelList = [ 'Cron', 'Scheduler' ];

        $handle = opendir( dirname( __DIR__ ) . '/models' );
        while ( false !== ( $file = readdir( $handle ) ) ) {
            if ( strpos( $file, '.php' ) === false) {
                continue;
            }

            $baseClassName = substr( $file, 0, -4 );
            if ( in_array( $baseClassName, self::$exemptModels ) ) {
                continue;
            }

            // If made it to here then this is a model that we can use
            $modelList[] = $baseClassName;
        }
        closedir( $handle );
        return $modelList;
    }
}
