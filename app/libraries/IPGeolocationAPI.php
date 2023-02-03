<?php
namespace App\Libraries;

class IPGeolocationAPI extends RestAPI {
    public $url;
    public $apiKey;

    public function __construct( $url, $apiKey ) {
        parent::__construct();
        $this->url    = $url;
        $this->apiKey = $apiKey;
    }

    // https://ipgeolocation.io/documentation/ip-geolocation-api.html
    public function singleIPGeoLookup( $ip ) {
        $url = sprintf( '$s/ipgeo?apiKey=%s&ip=%s', $this->url, $this->apiKey, $ip );
        return self::get( $url );
    }
}
