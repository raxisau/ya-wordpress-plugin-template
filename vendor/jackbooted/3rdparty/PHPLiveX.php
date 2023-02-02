<?PHP

use \Jackbooted\Html\Tag;
use \Jackbooted\Forms\Request;
use \Jackbooted\Html\JS;

#####################################
# PHPLiveX Library                  #
# (C) Copyright 2006 Arda Beyazoglu #
# Version: 2.5.1                    #
# Home Page: www.phplivex.com       #
# Contact: ardabeyazoglu@gmail.com  #
# License: LGPL                     #
# Release Date: 27.09.2008          #
#####################################

class PHPLiveX {

    private static $ignoredMethods = array( '__construct', '__destruct' );

    public static function create() {
        return new PHPLiveX ( );
    }

    /**
     * response Charset Encoding
     *
     * @var String
     */
    public $encoding = "UTF-8";

    /**
     * Array of ajaxified functions
     *
     * @var Array
     */
    private $functions = array();

    /**
     * Array of ajaxified object methods
     *
     * @var Array
     */
    private $objectMethods = array();

    /**
     * Indicates whether execute method was called before
     *
     * @var Boolean
     */
    private $executed = false;

    /**
     * Indicates whether run method was called before
     *
     * @var Boolean
     */
    private $ran = false;

    /**
     * Prepares specified functions for ajax requests
     *
     * @param Array $functions: array of function names to ajaxify
     */
    public function ajaxifyFunctions( $functions = array() ) {
        if ( !is_array( $functions ) )
            $functions = array( $functions );

        foreach ( $functions as $val ) {
            $val = stripslashes( trim( $val ) );
            if ( function_exists( $val ) ) {
                if ( !in_array( $val, $this->functions ) )
                    $this->functions[] = $val;
            }
            else {
                echo JS::showError( $val . 'function does not exist!' );
            }
        }

        reset( $this->functions );
        $this->execute();
        return $this;
    }

    /**
     * Prepares specified class methods for ajax requests
     *
     * @param Array $functions: array of objects to Ajaxify. The format is
     * array ( 'objectName1' => array ( 'ref' => object1Reference, 'methods' => array ( 'methodName1' .. 'N' ) ), // Selected Methods
     *         'objectName2' => array ( 'ref' => object2Reference ) // All methods
     *         ...
     *
     */
    public function ajaxifyObjects( $objectList = array() ) {
        foreach ( $objectList as $objectName => $objectInfo ) {

            if ( !isset( $objectInfo['ref'] ) )
                $objectInfo['ref'] = $objectName;

            // What is the name of this class
            $objectInfo['className'] = ( is_string( $objectInfo['ref'] ) ) ? $objectInfo['ref'] : get_class( $objectInfo['ref'] );

            // Save the method proxys
            if ( !isset( $objectInfo['methods'] ) )
                $objectInfo['methods'] = get_class_methods( $objectInfo['className'] );
            else if ( !is_array( $objectInfo['methods'] ) )
                $objectInfo['methods'] = array( $objectInfo['methods'] );

            // Set up property proxies
            $objectInfo['properties'] = get_class_vars( $objectInfo['className'] );

            // Save this for later
            $this->objectMethods[$objectName] = $objectInfo;
        }
        $this->execute();
        return $this;
    }

    public static function decode( $string, $encoding ) {
        return iconv( "UTF-8", $encoding . "//IGNORE", urldecode( $string ) );
    }

    /**
     * Calls the function specified by the incoming ajax request
     *
     */
    public function execute() {
        if ( $this->executed )
            return;

        $this->executed = true;
        if ( ( $function = Request::get( 'plxf' ) ) == '' )
            return;

        $args = Request::get( 'plxa', array() );

        if ( function_exists( "json_decode" ) ) {
            foreach ( $args as &$val ) {
                if ( preg_match( '/<plxobj[^>]*>(.|\n|\t|\r)*?<\/plxobj>/', $val, $matches ) ) {
                    $val = json_decode( substr( $matches[0], 8, -9 ) );
                }
            }
        }

        $response = '';
        $parts = explode( "::", $function );
        switch ( count( $parts ) ) {
            // Function Call
            case 1:
                $response = call_user_func_array( $function, $args );
                break;

            // Object Call
            case 2:
                if ( isset( $this->objectMethods[$parts[0]] ) ) {
                    $objectInfo = $this->objectMethods[$parts[0]];
                    $response = call_user_func_array( array( $objectInfo['ref'], $parts[1] ), $args );
                }
                else {
                    $response = call_user_func_array( array( $parts[0], $parts[1] ), $args );
                }
                break;

            default:
                $response = '';
                break;
        }

        if ( is_bool( $response ) ) {
            $response = (int) $response;
        }
        else if ( function_exists( "json_encode" ) && ( is_array( $response ) || is_object( $response ) ) ) {
            $response = json_encode( $response );
        }

        echo Tag::hTag( 'phplivex' ), $response, Tag::_hTag( 'phplivex' );
        exit();
    }

    /**
     * Creates the javascript reflections of the ajaxified php functions
     *
     * @param String $function
     * @return String JS code
     */
    private function createFunction( $function ) {
        return "function " . $function . "(){ return new PHPLiveX().Callback('" . $function . "', " . $function . ".arguments); }\n";
    }

    /**
     * Creates the javascript reflections of the ajaxified php objects
     *
     * @return String JS code
     */
    private function createClass( $objectName, $objectInfo ) {
        $methods = $objectInfo['methods'];
        $properties = $objectInfo['properties'];
        $elements = array();

        foreach ( $properties as $property => $value ) {
            if ( is_string( $value ) )
                $value = "'$value'";
            else if ( is_array( $value ) ||
                    is_object( $value ) && function_exists( "json_encode" ) )
                $value = json_encode( $value );
            if ( !isset( $value ) || $value == false )
                $value = "null";

            $elements[] = "'{$property}': {$value}";
        }

        foreach ( $methods as $method ) {
            if ( in_array( $method, self::$ignoredMethods ) )
                continue;

            $elements[] = "'{$method}': function(){ return new PHPLiveX().Callback({'obj': '{$objectName}', 'method': '{$method}'}, {$objectName}.{$method}.arguments); }";
        }

        return "var {$objectName} = {\n" .
                join( ",\n", $elements ) . "\n" .
                "};\n";
    }

    /**
     * Organizes the created javascript code for the page
     *
     * @param Boolean $includeJS: False to create js class here and True to include the js class file
     */
    public function run( $echoJS = true ) {
        if ( $this->ran )
            return;
        $this->ran = true;

        $js = '';
        foreach ( $this->functions as $function ) {
            $js .= $this->createFunction( $function );
        }

        foreach ( $this->objectMethods as $objectName => $objectInfo ) {
            $js .= $this->createClass( $objectName, $objectInfo );
        }

        if ( $echoJS )
            echo JS::javaScript( $js );
        else
            return $js;
    }

}
