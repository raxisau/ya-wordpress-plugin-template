<?php
namespace App\API;

class APIResult extends \Jackbooted\Util\JB {
    const OK           = 0;
    const JSON_ERROR   = 9001;
    const PARAM_ERROR  = 9002;
    const DATA_ERROR   = 9003;
    const SYSTEM_ERROR = 9004;

    private $result = [
        'error-code'=> self::OK,
    ];

    public static function init () {
        parent::init();
    }

    public function addError( $errorCode, ...$args ) {
        $this->result['error-code'] = $errorCode;
        if ( ! isset( $this->result['errors'] ) ) {
            $this->result['errors'] = [];
        }
        $this->result['errors'][] = join( ' ', $args );
        return $this;
    }

    public function addWarn( ...$args ) {
        if ( ! isset( $this->result['warnings'] ) ) {
            $this->result['warnings'] = [];
        }
        $this->result['warnings'][] = join( ' ', $args );
        return $this;
    }

    public function addMsg( ...$args ) {
        if ( ! isset( $this->result['messages'] ) ) {
            $this->result['messages'] = [];
        }
        $this->result['messages'][] = join( ' ', $args );
        return $this;
    }

    public function set( $keyOrArray, $val=null ) {
        if ( is_array( $keyOrArray ) ) {
            foreach ( $keyOrArray as $key => $val ) {
                $this->result[$key] = $val;
            }
        }
        else {
            $this->result[$keyOrArray] = $val;
        }
        return $this;
    }

    public function JSON() {
        return json_encode( $this->result, JSON_PRETTY_PRINT );
    }

    public function getResult() {
        return $this->result;
    }
}