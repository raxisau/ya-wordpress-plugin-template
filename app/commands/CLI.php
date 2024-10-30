<?php
namespace App\Commands;

class CLI extends \Jackbooted\Html\WebPage {
    const DEF = '\App\Commands\CLI->index()';

    private static $commands = null;
    private static $helpText = <<<TXT
    php jack.php <command> [-v] [-vv]
    <command> is one of the following:
    -v  Display debug statements
    -vv Display more debug statements
    ---------------------------------
        CLI:help            - Show this message
        -------------------------------------------------------------------------------------

    TXT;

    public static function init () {
        parent::init();

        $argv = $_SERVER['argv'];

        if ( in_array( '-v', $argv ) || in_array( '-vv', $argv ) ) {
            \App\App::debug();
        }
    }

    private static function initiate() {
        if ( self::$commands != null ) {
            return;
        }

        self::$commands = [
            'CLI:help' => '\App\Commands\CLI->help()',
        ];
        $cliDir = dirname( __FILE__ );
        $handle = opendir( $cliDir );
        while ( false !== ( $file = readdir( $handle ) ) ) {
            if ( strpos( $file, '.php' ) === false || in_array( $file, [ 'BaseCLI.php', 'CLI.php', 'CLIResult.php' ] ) ) {
                continue;
            }

            $fullClassName = '\App\Commands\\' . substr( $file, 0, -4 );

            self::$commands  = array_merge( self::$commands, $fullClassName::$commands );
            self::$helpText .= $fullClassName::$helpText . "\n";
        }
        closedir( $handle );
    }

    public function index() {
        $argv = $_SERVER['argv'];

        if ( in_array( '-h', $argv ) ) {
            return $this->help();
        }

        self::initiate();
        $ret = '';
        foreach ( $argv as $cmd ) {
            if ( isset( self::$commands[$cmd] ) ) {
                $ret .= self::execAction ( self::$commands[$cmd] ) . "\n";
            }
        }

        if ( $ret == '') {
            return $this->help();
        }

        return $ret;
    }

    public function help() {
        // Check the commands are valid
        self::initiate();
        foreach ( self::$commands as $cmd ) {
            $parts = preg_split ( '/(->)|(::)/' , $cmd );
            $clazz = $parts[0];

            if ( $clazz != '\\' . get_class( new $clazz ) ) {
                echo "Error: $clazz not found\n";
            }
        }

        return self::$helpText;
    }
}
