<?php

namespace Jackbooted\Html;

use \Jackbooted\Util\Log4PHP;

/** template.php - Templating Engine functions
 *
 * @copyright Confidential and copyright (c) 2023 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 *
 */

/**
 * preg_match ( "/(.*)<body[^>]*>/siU", $this->outputText, $results );
 * preg_match ( "/<\/body>(.*)$$/siU", $this->outputText, $results );
 *
 */
class Template extends \Jackbooted\Util\JB {

    const FILE = 'f';
    const STRING = 's';

    private $dataSource;
    private $type;
    private $tokenList = [];
    private $debugVars = [];
    private $outputText;
    private $log;

    public function __construct( $dataSource, $type = self::STRING ) {
        parent::__construct();

        $this->dataSource = $dataSource;
        $this->type = $type;
        $this->log = Log4PHP::logFactory( __CLASS__ );
    }

    public function replace( $token, $value = null ) {
        if ( is_array( $token ) ) {
            foreach ( $token as $key => $val ) {
                $this->debugVars[$key] = 1;
                $this->tokenList['{$' . $key . '}'] = $val ;
            }
        }
        else {
            if ( $value !== null ) {
                $this->debugVars[$token] = 1;
                $this->tokenList['{$' . $token . '}'] = $value;
            }
        }
        return $this;
    }

    private function loadDataSource() {
        switch ( $this->type ) {
            case self::FILE:
                $this->outputText = file_get_contents( $this->dataSource );
                $this->log->debug( 'File data source' );
                break;

            case self::STRING:
            default:
                $this->outputText = $this->dataSource;
                $this->log->debug( 'Text data source' );
                break;
        }
    }

    private function trimBodyTags() {
        if ( preg_match( "/<body[^>]*>(.*)<\/body>/siU", $this->outputText, $results ) ) {
            $this->outputText = $results[1];
            $this->log->debug( 'trimmed off body tags' );
        }
        else {
            $this->log->debug( 'No body tags to trim' );
        }

        $this->outputText = preg_replace( '/{\*.+?\*}/ms', '', $this->outputText );
    }

    private function doStraightReplacements() {
        $count = 0;
        $this->replace( 'debug_vars', '<pre>' . json_encode( array_keys( $this->debugVars ), JSON_PRETTY_PRINT ) . '</pre>' );
        $this->outputText = str_replace( array_keys( $this->tokenList ), array_values( $this->tokenList ), $this->outputText, $count );
        $this->log->debug( "replaced {$count} tokens" );
    }

    public function toTxt() {
        return $this->__toString();
    }

    public function toJSON() {
        return $this->__toString();
    }

    public function toHtml() {
        return $this->__toString();
    }

    public function __toString() {
        $this->loadDataSource();
        $this->trimBodyTags();
        $this->doStraightReplacements();

        return $this->outputText;
    }

}
