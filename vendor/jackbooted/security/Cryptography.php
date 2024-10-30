<?php

namespace Jackbooted\Security;

use \Jackbooted\Config\Cfg;
use \Jackbooted\Config\Config;
use \Jackbooted\G;
use \Jackbooted\Util\Log4PHP;
use \Defuse\Crypto\Key;
use \Defuse\Crypto\Crypto;

/*
 * @copyright Confidential and copyright (c) 2024 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */

class Cryptography extends \Jackbooted\Util\JB {

    const RAND_KEY = '4n5d73rde315E486pe9t7oy2nirefcPKnie0y126';
    const META = ':e:';
    const META_LEN = 3;
    const DMETA = ':d:';
    const DMETA_LEN = 3;
    const PADDING = '                                                  ';

    private static $instance = null;
    private static $log;
    private static $encryptionOff = false;

    public static function init() {
        if ( Cfg::get( 'crypto_location', 'session' ) == 'session' ) {
            if ( !isset( $_SESSION[G::SESS] ) ) {
                $_SESSION[G::SESS] = [];
            }
            if ( ! isset( $_SESSION[G::SESS][G::CRYPTO] ) ) {
                $iv = str_shuffle( Cfg::get( 'crypto_key', G::IV ) );
                $_SESSION[G::SESS][G::CRYPTO] = Config::get( 'session.crypto.key', $iv, Config::GLOBAL_SCOPE );
            }
        }
        self::$log = Log4PHP::logFactory( __CLASS__ );
        self::$encryptionOff = Cfg::get( 'encrypt_override' );
        self::$instance = new Cryptography ();
    }

    /**
     * General encryption method
     * @param string $plainText
     * @return string
     */
    public static function en( $plainText ) {
        return self::$instance->encrypt( $plainText );
    }

    /**
     * generalized decryption methd.
     * @param string $cypherText
     * @return string
     */
    public static function de( $cypherText ) {
        return self::$instance->decrypt( $cypherText );
    }

    public static function factory( $l_key = null ) {
        return new Cryptography( $l_key );
    }

    private $randKey;
    private $key;
    private $encryptionKey = null;

    /**
     *
     * @param string $l_key Key to use for encryption
     */
    public function __construct( $l_key = null ) {
        parent::__construct();

        $this->encryptionKey = $l_key;

        if ( Cfg::get( 'crypto_location', 'session' ) == 'session' ) {
            $this->randKey = md5( ( isset( $_SESSION[G::SESS][G::CRYPTO] ) ) ? $_SESSION[G::SESS][G::CRYPTO] : self::RAND_KEY );
        }
        else {
            $this->randKey = Cfg::get( 'crypto_key' );
        }

        $this->key = Key::createKey( ( $this->encryptionKey == null ) ? $this->randKey : $this->encryptionKey );
    }

    private $td = null;
    private $mcryptInit = false;
    private $blockLength;

    private function mcryptInit() {
        if ( $this->mcryptInit ) {
            return;
        }

        $this->mcryptInit = true;

        if ( !self::$encryptionOff ) {
            $algortithm = MCRYPT_RIJNDAEL_256;
        }

        $plainTextKey = ( $this->encryptionKey == null ) ? $this->randKey : $this->encryptionKey;

        $keySize = mcrypt_get_key_size( $algortithm, MCRYPT_MODE_ECB );
        while ( strlen( $plainTextKey ) < $keySize ) {
            $plainTextKey .= $plainTextKey;
        }

        $plainTextKey = substr( $plainTextKey, 0, $keySize );

        $this->blockLength = mcrypt_get_block_size( $algortithm, MCRYPT_MODE_ECB );
        $this->td = mcrypt_module_open( $algortithm, '', MCRYPT_MODE_ECB, '' );
        $iv = mcrypt_create_iv( mcrypt_enc_get_iv_size( $this->td ), MCRYPT_RAND );
        mcrypt_generic_init( $this->td, $plainTextKey, $iv );
    }

    /**
     * encrypt the passed string
     * @param string $input
     * @return string
     */
    public function encrypt( $plainText, $force = false ) {
        if ( self::$encryptionOff && !$force ) {
            return $plainText;
        }

        if ( !is_string( $plainText ) ) {
            $plainText = "{$plainText}";
        }

        if ( !isset( $plainText ) || strlen( $plainText ) == 0 ) {
            return $plainText;
        }

        try {
            $cypherText = self::DMETA . base64_encode( Crypto::encrypt( $plainText, $this->key, true ) );
        }
        catch ( \Exception $ex ) {
            self::$log->error( "Encryption error: " . $ex->getMessage() );
            $cypherText = $plainText;
        }

        return $cypherText;
    }

    public function decrypt( $cypherText ) {
        if ( strpos( $cypherText, self::DMETA ) === 0 ) {
            try {
                $plainText = Crypto::decrypt( base64_decode( substr( $cypherText, self::DMETA_LEN ) ), $this->key, true );
            }
            catch ( \Exception $ex ) {
                self::$log->error( "Decryption error: " . $ex->getMessage() );
                $plainText = $cypherText;
            }
        }
        else if ( strpos( $cypherText, self::META ) === 0 ) {
            $this->mCryptInit();
            $plainText = trim( mdecrypt_generic( $this->td, base64_decode( substr( $cypherText, self::META_LEN ) ) ) );
        }
        else {
            $plainText = $cypherText;
        }

        return $plainText;
    }
    /**
     * Clean up the crypto resources when they go out of scope
     */
    public function __destruct() {
        if ( !$this->mcryptInit ) {
            return;
        }

        if ( function_exists( 'mcrypt_generic_deinit' ) && $this->td != null ) {
            eval( 'mcrypt_generic_deinit ( $this->td );' );
        }
        if ( function_exists( 'mcrypt_module_close' ) && $this->td != null ) {
            eval( 'mcrypt_module_close ( $this->td );' );
        }
    }

}
