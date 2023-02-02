<?php

namespace Jackbooted\Util;

use \Jackbooted\Time\Stopwatch;

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

/**
 * The ClassLocator scans all the files in all the class folders and associates the name of the class
 * with the file that it is contained in. The way that it does this is by recursing through all the files
 * and looking for the string <b>class MyClass {</b> at the beginning of a line. The system then registers that
 * class as being in the associated file. This information is used by the AutoLoader to locate classes.
 *
 * This class is only used by the autoloader when it cannot fine the class by normal means.
 *
 * Once the array has been created in memory it is serialized out to file <i>(/tmp/ClassLocator.ser)</i>
 * for fast loading. If a class does not exist in the array6 when it is queried, then the file is recreated
 * This will ensure that you can create classes and the system will continue to know where they are.
 *
 * <b>Third Party Libraries</b><br>
 * You can load third party libraries with the autoloader by creating a class with a dummy class name in comments and
 * require_once the file containing the third party library. Example:
 * <pre>
 * &lt;?php
 * /*
 * The tag below will fool the ClassLocator to associating this class with this file
 * class Smarty
 * * /
 * // This is a dummy include so that the autoloader finds this class and loads it up
 * require_once( dirname ( dirname( __FILE__) ) . "/3rdparty/smarty/Smarty.class.php" );
 * ?&gt;
 * </pre>
 * @see AutoLoader
 */
class ClassLocator extends \Jackbooted\Util\JB {

    /**
     * Location of the serialization file
     */
    const LOCATOR_FILE = '/tmp/R_U_ClassLocator.ser';

    /**
     *
     * @var string Regular expression that searches for the classes, abstracts, interfaces etc
     */
    private static $regexClassSearch = '/^\s*\b(interface|trait|class|abstract\s*class|final\s*class)\b/';
    private static $regexNameSpaceSearch = '/namespace\s*([\\\\[:alnum:]]*)\s*/';
    private static $regexPHPFiles = '/^.*\.(php|class)$/';
    private static $defaultInstance;
    private static $log = null;

    /**
     * Initializes the system. This is called by the Autoloader
     * @param string $classDirectory You can pass in the name of the classes folder for the program to scan.
     */
    public static function init( $classDirectory = null ) {
        self::$log = Log4PHP::logFactory( __CLASS__ );
        if ( $classDirectory == null ) {
            $classDirectory = __DIR__;
        }
        self::$defaultInstance = new ClassLocator( $classDirectory );
    }

    public static function getLocation( $className ) {
        return self::$defaultInstance->getClassLocation( $className );
    }

    public static function getDefaultClassLocator() {
        return self::$defaultInstance;
    }

    private $locationArray;
    private $classesDir;
    private $locatorFile;

    public function __construct( $classDirectory = null ) {
        parent::__construct();
        $this->classesDir = $classDirectory;
        $this->locatorFile = PHPExt::getTempDir() . '/ClassLocator' . md5( var_export( $classDirectory, true ) ) . '.ser';
        self::$log->trace( "Locator File: {$this->locatorFile}" );
    }

    /**
     * Get the locator array. This is mostly used for testing, and not generally
     * required for most applications
     * @return array The locator array.
     */
    public function getLocatorArray() {
        return $this->locationArray;
    }

    /**
     * This is the method that you call to locate the class.
     * @param string $className Name of the class that youb are trying to locate
     * @return string The name of the file that it is contained in otherwise FALSE
     */
    public function getClassLocation( $className ) {
        if ( !isset( $this->locationArray ) ) {
            $this->loadArrayFromDisk();
        }

        // If the class location exists then send it back
        if ( isset( $this->locationArray[$className] ) &&
                file_exists( $this->locationArray[$className] ) ) {
            return $this->locationArray[$className];
        }
        else if ( substr( $className, 0, 1 ) == '\\' ) {
            $relativeClassName = substr( $className, 1 );
            if ( isset( $this->locationArray[$relativeClassName] ) &&
                    file_exists( $this->locationArray[$relativeClassName] ) ) {
                return $this->locationArray[$relativeClassName];
            }
        }

        // If made it to here then regenerate the array
        $this->locationArray = [];

        $timer = new Stopwatch( 'ClassLocator Regen' );
        if ( is_string( $this->classesDir ) ) {
            $cDir = $this->classesDir;
        }
        else if ( is_array( $this->classesDir ) ) {
            $cDir = join( ', ', $this->classesDir );
        }
        self::$log->info( "Regenerating class locator array ({$cDir})" );

        if ( is_string( $this->classesDir ) ) {
            $this->regenerateLocationArray( $this->classesDir );
        }
        else if ( is_array( $this->classesDir ) ) {
            foreach ( $this->classesDir as $dir ) {
                $this->regenerateLocationArray( $dir );
            }
        }

        $this->saveLocationArray();
        $timer->logLoadTime();
        if ( isset( $this->locationArray[$className] ) ) {
            return $this->locationArray[$className];
        }

        self::$log->error( "$className not found. Continual calls to this class will affect system performance" );
        self::$log->error( json_encode( debug_backtrace(), JSON_PRETTY_PRINT ) );
        return false;
    }

    /**
     * Loads the location array from the file
     * @return void
     */
    private function loadArrayFromDisk() {
        if ( !file_exists( $this->locatorFile ) ) {
            return;
        }

        $fd = fopen( $this->locatorFile, 'r' );
        if ( $fd === false ) {
            return;
        }

        $serializeLocator = fgets( $fd );
        $locatorArray = unserialize( $serializeLocator );
        fclose( $fd );

        if ( $locatorArray === false ) {
            return;
        }

        $this->locationArray = $locatorArray;
    }

    /**
     * Searches all the files in the passed directory and scans them for classes
     * @param string $classesDir
     */
    private function regenerateLocationArray( $classesDir ) {
        $handle = opendir( $classesDir );
        while ( false !== ( $file = readdir( $handle ) ) ) {
            if ( strpos( $file, '.' ) === 0 ) {
                continue;
            }

            $fullPathName = $classesDir . '/' . $file;
            if ( is_dir( $fullPathName ) ) {
                $this->regenerateLocationArray( $fullPathName );
            }
            else {
                $this->scanFileForClasses( $fullPathName );
            }
        }
        closedir( $handle );
    }

    /**
     * Scans the file for class declarations. Looks for name space declarations and
     * adds them to the class name
     * @param string $fullPathName
     * @return void
     */
    private function scanFileForClasses( $fullPathName ) {
        if ( !file_exists( $fullPathName ) ) {
            return;
        }

        // Do not bother with this file
        $fileNameMatches = null;
        if ( $fullPathName == __FILE__ ) {
            return;
        }

        if ( !preg_match( self::$regexPHPFiles, $fullPathName, $fileNameMatches ) ) {
            return;
        }

        $namespace = '';
        $nameSpaceMatches = null;

        $fd = fopen( $fullPathName, 'r' );
        while ( false !== ( $line = fgets( $fd ) ) ) {

            // Check if this has a name space.
            if ( preg_match( self::$regexNameSpaceSearch, $line, $nameSpaceMatches ) ) {
                if ( isset( $nameSpaceMatches[1] ) && $nameSpaceMatches[1] != false ) {
                    $namespace = $nameSpaceMatches[1] . '\\';
                }
            }

            if ( preg_match( self::$regexClassSearch, $line ) ) {
                $className = preg_replace( self::$regexClassSearch, '', $line );
                $className = preg_replace( '/\b(extends|implements).*/', '', $className );
                $className = preg_replace( '/\{.*/', '', $className );
                $className = preg_replace( '/\s*/', '', $className );
                $className = $namespace . $className;

                if ( isset( $this->locationArray[$className] ) ) {
                    self::$log->warn( "Duplicate class found ({$className}) in file {$fullPathName} and " . $this->locationArray[$className] );
                }
                $this->locationArray[$className] = $fullPathName;
            }
        }
        fclose( $fd );
    }

    /**
     * Saves the array out to disk
     * @return void
     */
    private function saveLocationArray() {

        $fd = fopen( $this->locatorFile, 'w' );
        if ( $fd === false ) {
            return;
        }

        fputs( $fd, serialize( $this->locationArray ) );
        fclose( $fd );
    }

    /**
     * Deletes the serialization file. Protected, only used for testing
     * @return void
     */
    public function getLocatorFile() {
        return $this->locatorFile;
    }

}
