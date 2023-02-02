<?php

namespace Jackbooted\Util;

/**
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */
class DataCache extends \Jackbooted\Util\JB {

    private static $log;

    public static function init() {
        self::$log = new Log4PHP( __CLASS__ );
    }

    private $timeStamps = [];
    private $hits = [];
    private $cache = [];
    private $maxSize = 0;
    private $cacheHits = 0;
    private $cacheMisses = 0;
    private $name = 0;

    public function __construct( $name, $size = 0 ) {
        parent::__construct();
        $this->maxSize = $size;
        $this->name = $name;
    }

    public function __destruct() {
        $msg = "Cache[{$this->name}][CacheHits]={$this->cacheHits} [CacheMisses]={$this->cacheMisses}";
        self::$log->trace( $msg );

        // I have commented this out incase you are interested in statistics
        //echo $msg . "<br/>\n";
        //foreach ( $this->timeStamps as $key => $val ) {
        //    echo $key . " Hits:" . $this->hits[$key] . "<br/>\n";
        //}
    }

    public function set( $key, $value ) {
        if ( !isset( $this->cache[$key] ) &&
                $this->maxSize > 0 &&
                count( $this->cache ) >= $this->maxSize ) {
            $this->removeOldestCacheValue();
        }

        $this->cache[$key] = $value;
        $this->touch( $key );
    }

    public function clear() {
        $this->timeStamps = [];
        $this->cache = [];
    }

    public function removeOldestCacheValue() {
        $maxTime = time();
        $maxKey = '';
        foreach ( $this->timeStamps as $key => $val ) {
            if ( $val <= $maxTime ) {
                $maxTime = $val;
                $maxKey = $key;
            }
        }
        unset( $this->timeStamps[$maxKey] );
        unset( $this->cache[$maxKey] );
    }

    public function get( $key ) {
        if ( isset( $this->cache[$key] ) ) {
            $this->touch( $key );
            $this->cacheHits ++;
            return $this->cache[$key];
        }
        else {
            $this->cacheMisses ++;
            return false;
        }
    }

    private function touch( $key ) {
        $this->timeStamps[$key] = time();
        if ( isset( $this->hits[$key] ) ) {
            $this->hits[$key] ++;
        }
        else {
            $this->hits[$key] = 1;
        }
    }

}
