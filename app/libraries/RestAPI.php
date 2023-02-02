<?php
namespace App\Libraries;

class RestAPI extends \Jackbooted\Util\JB {
    const AGENT_BRAVE  = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.51 Safari/537.36';
    const AGENT_GOOGLE = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    protected static $stdHeaders = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    public static function get( $url, $headers=[], $agent=self::AGENT_BRAVE ) {
        $culrOpts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_USERAGENT      => $agent,
            CURLOPT_HTTPHEADER     => array_merge( self::$stdHeaders, $headers ),
        ];
        return self::call( $culrOpts );
    }

    public static function post( $url, $postData='', $headers=[], $agent=self::AGENT_BRAVE ) {
        return self::send( 'POST', $url, $postData, $headers, $agent );
    }

    public static function delete( $url, $postData='', $headers=[], $agent=self::AGENT_BRAVE ) {
        return self::send( 'DELETE', $url, $postData, $headers, $agent );
    }

    private static function send( $method, $url, $postData='', $headers=[], $agent=self::AGENT_BRAVE ) {
        if ( is_array( $postData ) ) {
            $postData = json_encode( $postData );
        }

        $culrOpts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_USERAGENT      => $agent,
            CURLOPT_HTTPHEADER     => array_merge( self::$stdHeaders, $headers ),
        ];

        $response = self::call( $culrOpts );
        return $response;
    }

    public static function call( $culrOpts ) {
        try {
            $ch = curl_init( $culrOpts[CURLOPT_URL] );
            curl_setopt_array( $ch, $culrOpts );

            if ( ( $result = curl_exec( $ch ) ) === false ) {
                return [
                    'error'     => true,
                    'msg'       => 'cURL error: ' . curl_error( $ch ) . ' #' . curl_errno( $ch ),
                    'result'    => false,
                    'url'       => $culrOpts[CURLOPT_URL],
                    'http_code' => 0,
                ];
            }

            if ( ( $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE ) ) != 200 ) {
                return [
                    'error'     => true,
                    'msg'       => 'Invalid HTTP Code: ' . $httpCode,
                    'result'    => $result,
                    'url'       => $culrOpts[CURLOPT_URL],
                    'http_code' => $httpCode,
                ];
            }

            if ( ( $obj = json_decode( $result, true ) ) === null ) {
                if ( $result == "Ok" ) {
                    return [
                        'error'     => false,
                        'msg'       => 'Ok',
                        'result'    => $result,
                        'url'       => $culrOpts[CURLOPT_URL],
                        'http_code' => $httpCode,
                    ];
                }
                else {
                    return [
                        'error'     => true,
                        'msg'       => 'Invalid JSON String',
                        'result'    => $result,
                        'url'       => $culrOpts[CURLOPT_URL],
                        'http_code' => $httpCode,
                    ];
                }
            }

            return [
                'error'     => false,
                'msg'       => 'Ok',
                'result'    => $obj,
                'url'       => $culrOpts[CURLOPT_URL],
                'http_code' => $httpCode,
            ];
        }
        finally {
            curl_close( $ch );
        }
    }
}
