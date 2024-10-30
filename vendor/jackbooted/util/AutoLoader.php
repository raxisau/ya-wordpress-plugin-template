<?php

namespace Jackbooted\Util;

/**
 * @copyright Confidential and copyright (c) 2024 Jackbooted Software. All rights reserved.
 *
 * Written by Brett Dutton of Jackbooted Software
 * brett at brettdutton dot com
 *
 * This software is written and distributed under the GNU General Public
 * License which means that its source code is freely-distributed and
 * available to the general public.
 */

/**
 * The day of require_once is now over for classes. This class will automatically set itself up to load any class from
 * the classes folder and any of it's sub folders. The folowing code initializes and sets up the automatic class loader
 * <pre>
 * require_once ( Cfg::get ( 'classes_dir' ) . "/util/Log4PHP.inc" );
 * if ( Cfg::get ( 'development') ) {
 *     Log4PHP::setLogLevel ( Log4PHP::ALL );
 * }
 *
 * // need to require once the autoloader
 * require_once ( Cfg::get ( 'classes_dir' ) . "/util/AutoLoader.inc" );
 * AutoLoader::init ( Cfg::get ( 'classes_dir' ) );
 * </pre>
 *
 * The ClassLocator scans all the files in all the sub folders looking for the class that you are trying to load.
 *
 * <b>Third Party Libraries</b><br>
 * You can load third party libraries with the autoloader by creating a class with a dummy class name in comments and
 * require_once the file containing the third party library. Example:
 *
 * <pre>
 * &lt;?php
 * /*
 * The tag below will fool the ClassLocator to associating this class with this file
 * class Smarty
 * * /
 * // This is a dummy include so tha the autoloader finds this class and loads it up
 * require_once( dirname ( dirname( __FILE__) ) . "/3rdparty/smarty/Smarty.class.php" );
 * ?&gt;
 * </pre>
 *
 * @see ClassLocator
 */
class AutoLoader extends \Jackbooted\Util\JB {

    /**
     * Suffix for classes that are auto loaded
     */
    const CLASS_SUFFIX = '.php';
    const THIRD_PARTY_REGEX = '/^.*\/3rdparty.*$/';

    /**
     * initialization method
     */
    const STATIC_INIT = 'init';

    private static $log;
    private static $ignoreList = [];

    /**
     * load set up the static variables of the class
     * (still waiting on static initialisation from PHP)
     * @param string $classesDir
     */
    public static function init() {
        spl_autoload_register( __CLASS__ . '::autoload' );
        self::$log = Log4PHP::logFactory( __CLASS__ );
    }

    /**
     * Adds a class to the ignore list.
     * @param String $className
     * @param String $fullName This is the full Java class Name.
     * @return void
     */
    public static function ignore( $className, $fullName = null ) {
        self::$ignoreList[$className] = true;
    }

    /**
     * This method is called by the autoloader - Registered somewhere (config)
     * with spl_autoload_register ( 'AutoLoader::autoload' );
     * This class must be manually loaded with require_one and then call init
     * @param string $className Class name that needs to be loaded
     */
    public static function autoload( $className ) {
        if ( isset( self::$ignoreList[$className] ) ) {
            return;
        }

        // There might be multiple instances of Jackbooted
        if ( class_exists( $className, false) ) {
            return;
        }

        if ( preg_match( '/^(Jackbooted|App|Defuse|Shuchkin|PHPMailer)\\\\.*$/', $className, $matches1 ) === 1 ||
             preg_match( '/^(setasign|FPDF|FeedItem|FeedWriter|PHPLiveX|SiteMap|Upload|BAR_GRAPH)$/', $className, $matches2 ) === 1 ) {

            if ( ( $tries = self::locateClassFromFileAndLoad( $className ) ) !== true ) {
                self::$log->error( 'The system has attempted to autoload non existing class: ' . $className . ' tried: (' . implode( ', ', $tries ) . ')' );
            }
        }
        else {
            self::$log->trace( "Skipping {$className} because it is not in pregmatch" );
        }
    }

    /**
     * Loads a class based on the class name for legacy classes
     * @param string $className This is the class that you are attemption to load
     * @return mixed Return true on successful load, otherwise it returns a list of all the file locations
     * that it tried to load from
     */
    private static function locateClassFromFileAndLoad( $className ) {
        $fileToLoad = ClassLocator::getLocation( $className );
        if ( $fileToLoad === false ) {
            return [ 'none found' ];
        }

        if ( self::loadClassFromFile( $className, $fileToLoad ) ) {
            return true;
        }

        return [ $fileToLoad ];
    }

    /**
     * Loads a class from the passed file. Attempts to initialize the class
     * @param string $className This is the class that youb are attemption to load
     * @param string $fileToLoad The file that you are loading the class from
     * @return boolean true on sucess
     */
    public static function loadClassFromFile( $className, $fileToLoad ) {
        // If the file does not exist then get out
        if ( !file_exists( $fileToLoad ) ) {
            self::$log->error( "File does not exist for {$className} from $fileToLoad" );
            return false;
        }

        // Everything good!
        require_once $fileToLoad;

        // RUn class level initialization only on classes that follow JackBoot Web standard
        if ( ! preg_match( self::THIRD_PARTY_REGEX, $fileToLoad ) ) {
            self::runClassInitialization( $className );
        }

        self::$log->trace( "Loaded {$className} from " . $fileToLoad );
        return true;
    }

    /**
     * Check to see if there is class level initialisation and then runs it
     * Need this because PHP does not have static initialisation yet
     * @param string $className to initialise
     */
    private static function runClassInitialization( $className ) {
        if ( method_exists( $className, self::STATIC_INIT ) ) {
            call_user_func( [ $className, self::STATIC_INIT ] );
        }
    }
}
