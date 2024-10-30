<?php
namespace App\Commands;

class CLIResult extends \Jackbooted\Util\JB {

    private $result = [
        'processed'=> 0,
    ];

    private $debug;

    public static function init () {
        parent::init();
    }

    public function __construct( ) {
        parent::__construct();
        $this->debug = in_array( '-v', $_SERVER['argv'] ) || in_array( '-vv', $_SERVER['argv'] );
    }

    public function processed( $num=1, $type='processed' ) {
        if ( ! isset( $this->result[$type] ) ) {
            $this->result[$type] = 0;
        }

        if ( is_numeric( $num ) ) {
            $this->result[$type] += $num;
        }
        return $this;
    }
    public function error( $arg ) {
        $this->set( 'error', $arg );
        return $this;
    }

    public function warning( $arg ) {
        $this->set( 'warning', $arg );
        return $this;
    }

    public function success( $arg ) {
        $this->set( 'success', $arg );
        return $this;
    }

    public function debug( $arg ) {
        $this->set( 'debug', $arg );
        return $this;
    }

    private function set( $type, $valOrArray ) {
        if ( ! isset( $this->result[$type] ) ) {
            $this->result[$type] = [];
        }

        $this->result[$type][] = $valOrArray;

        if ( $this->debug ) {
            if ( is_array( $valOrArray ) ) {
                echo strtoupper( $type ) . ' ' . json_encode( $valOrArray, JSON_PRETTY_PRINT ) . "\n";
            }
            else {
                echo strtoupper( $type ) . " {$valOrArray}\n";
            }
        }
    }

    public function JSON() {
        $silent = ( in_array( '-s', $_SERVER['argv'] ) );

        if ( $silent && ( ! isset( $this->result['error'] ) || count( $this->result['error'] ) == 0 ) ) {
            return '';
        }
        else {
            if ( ! $this->debug ) {
                unset( $this->result['debug'] );
            }
            return json_encode( $this->result, JSON_PRETTY_PRINT ) . "\n";
        }
    }

    public function getResult() {
        return $this->result;
    }
}