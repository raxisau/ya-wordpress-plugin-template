<?php

namespace Jackbooted\Html;

use \Jackbooted\Util\Invocation;

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
 * Generates the javascript validation based on the required directives.
 *
 * After you initialise the
 * class with the form name you call methods to direct the style of validation. Then call to method
 * <i>toHtml</i> to generate the javascript needed for the validation.
 *
 * Current validation functions are:
 * <ul>
 * <li>Exists</li>
 * <li>String Length</li>
 * <li>Numerical Range</li>
 * <li>Is Integer</li>
 * <li>Valid Email</li>
 * <li>2 fields are equal (password)</li>
 * <li>Duplicate Field (prefered name)</li>
 * <li>24Hr Time Check</li>
 * </ul>
 *
 * <b>PLease Note. This dependends on jquery!</b>
 *
 * <h4>Example of use</h4>
 * <pre>
 * &lt;?php
 * require_once ( dirname ( dirname ( dirname (  __FILE__ ) ) ) . &quot;/config.php&quot; );
 *
 * $val = new Validator( 'testFormName' );
 * $val-&gt;setMissingAlert( true );
 *
 * $val-&gt;addExists( 'Exists', 'Exists Description' );
 * $val-&gt;addExists( 'Length1', 'Length1 Description' );
 * $val-&gt;addLength( 'Length1', 'Length1 Description', 5, 5 );
 * $val-&gt;addExists( 'Length2', 'Length2 Description' );
 * $val-&gt;addLength( 'Length2', 'Length2 Description', 2, 6 );
 * $val-&gt;addExists( 'Length3', 'Length3 Description' );
 * $val-&gt;addLength( 'Length3', 'Length3 Description', 2 );
 * $val-&gt;addExists( 'Length4', 'Length4 Description' );
 * $val-&gt;addLength( 'Length4', 'Length4 Description', null, 5 );
 * $val-&gt;addExists( 'Range1', 'Range1 Description' );
 * $val-&gt;addRange( 'Range1', 'Range1 Description', 5, 5 );
 * $val-&gt;addExists( 'Range2', 'Range2 Description' );
 * $val-&gt;addRange( 'Range2', 'Range2 Description', 2, 6 );
 * $val-&gt;addExists( 'Range3', 'Range3 Description' );
 * $val-&gt;addRange( 'Range3', 'Range3 Description', 2 );
 * $val-&gt;addExists( 'Range4', 'Range4 Description' );
 * $val-&gt;addRange( 'Range4', 'Range4 Description', null, 5 );
 * $val-&gt;addExists( 'Integer', 'Integer Description' );
 * $val-&gt;addInteger( 'Integer', 'Integer Description' );
 * $val-&gt;addExists( 'Email', 'Email Description' );
 * $val-&gt;addEmail( 'Email', 'Email Description' );
 * $val-&gt;addExists( 'Equal1', 'Equal Description' );
 * $val-&gt;addEqual( 'Equal1', 'Equal2', 'Equal Description' );
 * $val-&gt;addCopy( 'Copy1', 'Copy2' );
 * $val-&gt;addExists( 'Copy1', 'Copy 1 descr' );
 * $val-&gt;add24HrTime( '24HR', 'Time needs to be HH:MM' );
 * $html = $val-&gt;toHtml();
 * echo '&lt;a href=&quot;javascript:void(0)&quot; onclick=&quot;jQuery(\'#code\').toggle()&quot;&gt;Hide/Show Javascript Output&lt;/a&gt;&lt;br/&gt;';
 * echo '&lt;div id=&quot;code&quot; style=&quot;display:none&quot;&gt;&lt;pre&gt;' . htmlspecialchars( $html ) . '&lt;/pre&gt;&lt;/div&gt;';
 * echo '&lt;a href=&quot;javascript:void(0)&quot; onclick=&quot;jQuery(\'#vars\').toggle()&quot;&gt;Hide/Show GET Variables&lt;/a&gt;&lt;br/&gt;';
 * if ( count ( $_GET ) &gt; 0 ) {
 *     echo '&lt;div id=&quot;vars&quot; style=&quot;display:none&quot;&gt;&lt;pre&gt;';
 *     print_r( $_GET );
 *     echo '&lt;/pre&gt;&lt;/div&gt;';
 * }
 *
 * echo $html;
 * echo '&lt;form name=&quot;testFormName&quot; onSubmit=&quot;' . $val-&gt;onSubmit() . '&quot;&gt;';
 * echo 'Exists'  . Tag::text( 'Exists',  array ( 'value' =&gt; 'xyz' ) ) . '&lt;br&gt;';
 * echo 'Length1' . Tag::text( 'Length1', array ( 'value' =&gt; '12345' ) ) . '&lt;br&gt;';
 * echo 'Length2' . Tag::text( 'Length2', array ( 'value' =&gt; '123' ) ) . '&lt;br&gt;';
 * echo 'Length3' . Tag::text( 'Length3', array ( 'value' =&gt; '12345' ) ) . '&lt;br&gt;';
 * echo 'Length4' . Tag::text( 'Length4', array ( 'value' =&gt; '1234' ) ) . '&lt;br&gt;';
 * echo 'Range1'  . Tag::text( 'Range1',  array ( 'value' =&gt; '5' ) ) . '&lt;br&gt;';
 * echo 'Range2'  . Tag::text( 'Range2',  array ( 'value' =&gt; '5' ) ) . '&lt;br&gt;';
 * echo 'Range3'  . Tag::text( 'Range3',  array ( 'value' =&gt; '4' ) ) . '&lt;br&gt;';
 * echo 'Range4'  . Tag::text( 'Range4',  array ( 'value' =&gt; '4' ) ) . '&lt;br&gt;';
 * echo 'Integer' . Tag::text( 'Integer', array ( 'value' =&gt; '10' ) ) . '&lt;br&gt;';
 * echo 'Email'   . Tag::text( 'Email',   array ( 'value' =&gt; 'a@b.c' ) ) . '&lt;br&gt;';
 * echo 'Equal1'  . Tag::text( 'Equal1',  array ( 'value' =&gt; 'A1' ) ) . '&lt;br&gt;';
 * echo 'Equal2'  . Tag::text( 'Equal2',  array ( 'value' =&gt; 'A1' ) ) . '&lt;br&gt;';
 * echo 'Copy1'   . Tag::text( 'Copy1',   array ( 'value' =&gt; 'BB' ) ) . '&lt;br&gt;';
 * echo 'Copy2'   . Tag::text( 'Copy2' ) . '&lt;br&gt;';
 * echo '24Hr'    . Tag::text( '24HR',    array ( 'value' =&gt; '01:02' ) ) . '&lt;br&gt;';
 * echo Tag::submit( 'Submit', 'submit' ) . '&lt;br&gt;';
 * echo Response::factory()-&gt;toHidden(false);
 * echo '&lt;/form&gt;';
 * </pre>
 */
class Validator extends \Jackbooted\Util\JB {

    /**
     * Enumerated list of functions that are available.
     */
    const FN_EXISTS = 'EXISTS';

    /**
     * Function to check a range.
     */
    const FN_RANGE = 'RANGE';

    /**
     * Function to check if the field is a valid integer.
     */
    const FN_INTEGER = 'INTEGER';

    /**
     * Check to see if the field is a valid email.
     */
    const FN_EMAIL = 'EMAIL';

    /**
     * Check to see if 2 fields are the same.
     */
    const FN_EQUAL = 'EQUAL';

    /**
     * Copies one field to another if the second one is empty.
     */
    const FN_COPY = 'COPY';

    /**
     * Validates if the field is a particular length or range.
     */
    const FN_LENGTH = 'LENGTH';

    /**
     * Validates if the field is 24hr time.
     */
    const FN_24HRTIME = '24HRTIME';

    /**
     * Validates if the field is MySQL date time.
     */
    const FN_MYSQLDATETIME = 'MYSQLDATETIME';

    public static function factory( $formName, $suffix = '' ) {
        return new Validator( $formName, $suffix );
    }

    /**
     * Name of the form in this document.
     * @var String
     */
    private $formName;

    /**
     * An array of all the form variables that will be tested.
     * @see $add
     * @var array
     */
    private $testCases = [];

    /**
     * List of used functions.
     *
     * This is used so we
     * do not generate javascript that we don't need.
     * @var array
     */
    private $usedFunct = [];

    /**
     * This variable will alert the user to any form variables that are missing.
     *
     * This is not used in anything other than testing.
     * @var array
     */
    private $alertOnMissingFormVar = FALSE;

    /**
     * @var int is a unique name for the javascript. Automatically generated.
     */
    private $id;
    private $headerJS;
    private $existsJS;
    private $emailJS;
    private $integerJS;
    private $validateHeaderJS;
    private $testCaseHeaderJS;
    private $caseExistsJS;
    private $caseLenEqJS;
    private $caseLenBetweenJS;
    private $caseLenGTJS;
    private $caseLenLTJS;
    private $caseIntegerJS;
    private $caseRangeBetweenJS;
    private $caseRangeGTJS;
    private $caseRangeLTJS;
    private $caseEmailJS;
    private $caseEqualJS;
    private $caseCopyJS;
    private $caseAlertMissingJS;
    private $validateFooterJS;
    private $validateFunctionJS;
    private $case24HrTimeJS;
    private $t24HrTimeJS;
    private $caseMySQLDateTimeJS;

    /**
     * Creates the Validation object.
     *
     * Requires the form name.
     *
     * @param string $formName The name of the form that will be validated.
     * @param string $suffix The suffix will give the unique identifier if there are a number of
     * validators on a page. The uniquie suffix is automatically generated based on number of
     * invokations of the form. This does not work on ajax late generated forms
     * so for ajax, supply a unique suffix
     *
     * @since 1.0
     */
    public function __construct( $formName, $suffix = '' ) {
        parent::__construct();

        if ( $suffix == '' ) {
            $suffix = Invocation::next();
        }
        $this->formName = $formName;
        $this->id = '_' . $suffix;
        $this->setUpJavaScriptFunctions();
    }

    /**
     * Sets the variable alertOnMissingFormVar.
     *
     * This variable controlles
     * is there is a message displayed if the javascript is given an
     * invalid form variable name to test.
     *
     * @param boolean $state The state of this variable.
     *
     * @since 1.0
     * @return void
     */
    public function setMissingAlert( $state ) {
        $this->alertOnMissingFormVar = $state;
        return $this;
    }

    /**
     * Add a test for this form variable.
     *
     * Tests for Existance.
     *
     * @param string $fv   Form Variable name.
     * @param string $desc A message if the test fails.
     *
     * @since 1.0
     * @return void
     */
    public function addExists( $fv, $desc ) {
        return $this->add( $fv, $desc, self::FN_EXISTS );
    }

    /**
     *  Add a test for this form variable.
     *
     * Tests for a length.
     * if $minLength is set and $maxLength then the test will ensure that
     * the field is at least $minLength long. The same goes for $maxLength set and
     * $minLength not set. The field can be a maximum of maxLength long
     * If they are both set then the field must ve between the 2 lengths
     * If they are the same then the field must be exactly that length.
     * If both $minLength and $maxLength are null then no tests are performed.
     *
     * @param string  $fv        Form Variable name.
     * @param string  $desc      A message if the test fails.
     * @param integer $minLength Minimum length of string.
     * @param integer $maxLength Maximum length of string.
     *
     * @since 1.0
     * @return void
     */
    public function addLength( $fv, $desc, $minLength = null, $maxLength = null, $zeroOk = 'false' ) {
        return $this->add( $fv, $desc, self::FN_LENGTH, [ $minLength, $maxLength, $zeroOk ] );
    }

    /**
     * Add a test that a field is within a particular range.
     *
     * The range works in
     * the same sort of way as the length checking. If both variables are set then the
     * field value must be between the max and min. If the min is set and max not set then
     * the value must be greater than min, and visa-versa
     * If both $mn and $mx are null then no tests are performed.
     *
     * @param string $fv   Form Variable name.
     * @param string $desc A message if the test fails.
     * @param float  $mn   Minimum value of the field.
     * @param float  $mx   Maximum value of the field.
     *
     * @since 1.0
     * @return void
     */
    public function addRange( $fv, $desc, $mn = null, $mx = null ) {
        return $this->add( $fv, $desc, self::FN_RANGE, [ $mn, $mx ] );
    }

    /**
     * Add a test for this form variable.
     *
     * Tests for value being an integer
     * ensures that the field contains a valid integer. Note that empty is valid as well
     * Usually you check for existance before you test for integer.
     *
     * @param string $fv   Form Variable name.
     * @param string $desc A message if the test fails.
     *
     * @since 1.0
     * @return void
     */
    public function addInteger( $fv, $desc ) {
        return $this->add( $fv, $desc, self::FN_INTEGER );
    }

    /**
     * Add a test for this form variable.
     *
     * Tests for valid email
     * Note that empty is valid. That means that you must check for
     * Existance as well as email.
     *
     * @param string $fv   The form variable name to test.
     * @param string $desc The description of the error.
     *
     * @since 1.0
     * @return void
     */
    public function addEmail( $fv, $desc ) {
        return $this->add( $fv, $desc, self::FN_EMAIL );
    }

    /**
     * Add a test for this form variable.
     *
     * Tests for valid 24 hour time
     *
     * @param string $fv   The form variable name to test.
     * @param string $desc The description of the error.
     *
     * @since 2.0
     * @return void
     */
    public function add24HrTime( $fv, $desc, $defaultTime = '' ) {
        $this->usedFunct[self::FN_INTEGER] = 'YES';
        return $this->add( $fv, $desc, self::FN_24HRTIME, $defaultTime );
    }

    /**
     * Add a test for this form variable - mysql database date time .
     *
     * Tests for valid mysql database date time YYYY-MM-DD HH:MM:SS
     *
     * @param string $fv   The form variable name to test.
     * @param string $desc The description of the error.
     *
     * @since 2.0
     * @return void
     */
    public function addMySQLDateTime( $fv, $desc ) {
        $this->usedFunct[self::FN_MYSQLDATETIME] = 'YES';
        return $this->add( $fv, $desc, self::FN_MYSQLDATETIME );
    }

    /**
     * Add a test for this form variable.
     *
     * Tests for 2 form variables
     * are equal. This is particually useful for passwords
     *
     * @param string $fv1  First form variable to check.
     * @param string $fv2  Other form variable to test againse.
     * @param string $desc Message if the variables are not the same.
     *
     * @since 1.0
     * @return void
     */
    public function addEqual( $fv1, $fv2, $desc ) {
        return $this->add( $fv1, $desc, self::FN_EQUAL, $fv2 );
    }

    /**
     * Add a test for this form variable.
     *
     * Tests if a form variable
     * is empty. If it is then it copies the value from $fv1 into $fv2
     * This would be useful for say prefered name.
     *
     * @param string $fv1 Source Form Variable.
     * @param string $fv2 Destination Form Variable.
     *
     * @since 1.0
     * @return void
     */
    public function addCopy( $fv1, $fv2 ) {
        return $this->add( $fv1, '', self::FN_COPY, $fv2 );
    }

    /**
     * Generic function for adding tests.
     *
     * This is only called internally.
     *
     * @param string $fv   Form Variable.
     * @param string $desc Description for this test.
     * @param enum $t      Test type.
     * @param string $xtra Extra information.
     *
     * @since 1.0
     * @return void
     */
    private function add( $fv, $desc, $t, $xtra = NULL ) {
        $this->testCases[] = [ 'NAME' => $fv,
            'DESC' => $desc,
            'TEST' => $t,
            'XTRA' => $xtra ];
        $this->usedFunct[$t] = 'YES';
        return $this;
    }

    /**
     * Generates HTML and Javascript to do the tests on this form.
     *
     * @since 1.0
     * @return string
     */
    public function toHtml() {
        $msg = $this->headerJS;

        foreach ( $this->usedFunct as $key => $val ) {
            $msg .= $this->addJSFunctions( $key );
        }

        // Then create the validation function that will test all the
        // different form variables
        $msg .= sprintf( $this->validateHeaderJS, $this->formName );

        foreach ( $this->testCases as $val ) {
            $nam = $val['NAME'];
            $desc = $val['DESC'];
            $xtra = $val['XTRA'];

            // Test is in java and check if the form variable exists
            // Ensures the Javascript does not crash on
            // missing Form Variables
            $msg .= sprintf( $this->testCaseHeaderJS, $nam );

            $msg .= $this->createCaseJSTests( $val, $xtra, $desc );

            // End of the if test that check if the form var exists
            $msg .= sprintf( $this->validateFooterJS, $this->formName );

            // Check if we are notifying the user on missing form vars
            if ( $this->alertOnMissingFormVar ) {
                $msg .= sprintf( $this->caseAlertMissingJS, $nam );
            }
        }

        $msg .= sprintf( $this->validateFunctionJS, $this->formName );
        return JS::library( JS::JQUERY ) .
               JS::javaScript( $msg );
    }

    private function createCaseJSTests( $val, $xtra, $desc ) {
        // Output the javascript to do the different checks
        switch ( $val['TEST'] ) {
            case self::FN_EXISTS:
                return sprintf( $this->caseExistsJS, $desc );

            case self::FN_LENGTH:
                return $this->lengthJSCases( $xtra, $desc );

            case self::FN_INTEGER:
                return sprintf( $this->caseIntegerJS, $desc );

            case self::FN_RANGE:
                return $this->rangeJSCases( $xtra, $desc );

            case self::FN_EMAIL:
                return sprintf( $this->caseEmailJS, $desc );

            case self::FN_24HRTIME:
                return sprintf( $this->case24HrTimeJS, $desc, $val['XTRA'], $val['XTRA'] );

            case self::FN_MYSQLDATETIME:
                return sprintf( $this->caseMySQLDateTimeJS, $desc );

            case self::FN_EQUAL:
                return sprintf( $this->caseEqualJS, $val['XTRA'], $desc );

            case self::FN_COPY:
                return sprintf( $this->caseCopyJS, $val['XTRA'], $desc );

            default:
                return '';
        }
    }

    private function rangeJSCases( $xtra, $desc ) {
        if ( $xtra[0] != NULL && $xtra[1] != NULL ) {
            return sprintf( $this->caseRangeBetweenJS, $xtra[0], $xtra[1], $desc );
        }
        else if ( $xtra[0] != NULL ) {
            return sprintf( $this->caseRangeGTJS, $xtra[0], $desc );
        }
        else if ( $xtra[1] != NULL ) {
            return sprintf( $this->caseRangeLTJS, $xtra[1], $desc );
        }
        else {
            return '';
        }
    }

    private function lengthJSCases( $xtra, $desc ) {
        if ( $xtra[0] != NULL && $xtra[1] != NULL ) {
            if ( $xtra[0] == $xtra[1] ) {
                return sprintf( $this->caseLenEqJS, $xtra[2], $xtra[0], $desc );
            }
            else {
                return sprintf( $this->caseLenBetweenJS, $xtra[2], $xtra[0], $xtra[1], $desc );
            }
        }
        else if ( $xtra[0] != NULL ) {
            return sprintf( $this->caseLenGTJS, $xtra[2], $xtra[0], $desc );
        }
        else if ( $xtra[1] != NULL ) {
            return sprintf( $this->caseLenLTJS, $xtra[2], $xtra[1], $desc );
        }
        else {
            return '';
        }
    }

    private function addJSFunctions( $key ) {
        switch ( $key ) {
            case self::FN_EXISTS:  // Output the javascript functions for Existance
                return $this->existsJS;

            case self::FN_EMAIL: // Output the javascript functions for email
                return $this->emailJS;

            case self::FN_24HRTIME: // Output the javascript functions for time validation
                return $this->t24HrTimeJS;

            case self::FN_INTEGER:
                $ret = $this->integerJS;
                $this->integerJS = '';
                return $ret;


            default:
                return '';
        }
    }

    /**
     * Creates the javascript that can be included in attribute that will validate the form.
     *
     * Typically this would be used in the creation of the form so that it does the validation.
     * <pre>
     * echo '&lt;form onSubmit="' . $val->onSubmit () . '"&gt;';
     * </pre>
     *
     * @param string $js Additional javascript.
     *
     * @since 1.0
     * @return string
     */
    public function onSubmit( $js = '' ) {
        return 'if(!' . $this->jsValidate() . ')return false;' . $js . 'return true;';
    }

    /**
     * Returns the javascript that is just used for doing the validation.
     *
     * Typically this would be used in the javascript when you have to integrate with other validation.
     * <pre>
     * &lt;script language="JavaScript"&gt;
     *     function saveClicked() {
     *         if ( ! &lt;?php echo $valid-&gt;jsValidate(); ?&gt; ) {
     *             return;
     *         }
     *         var f = document.forms[0];
     *         if (f['issues[]'].selectedIndex &lt; 0) {
     *             if (!confirm('There is no issue selected.')) {
     *                 return;
     *             }
     *         }
     *         top.restoreSession();
     *         f.submit();
     *     }
     * &lt;/script&gt;
     * </pre>
     *
     * @since 2.0
     * @return string
     */
    public function jsValidate() {
        return "validateForm{$this->id}()";
    }

    private function setUpJavaScriptFunctions() {
        $this->headerJS = <<<JS
            function isEmpty{$this->id}( s ) {
                return ( ( s == null ) || ( s.length == 0 ) );
            }
            var whitespace{$this->id} = " \\t\\n\\r";
            function isWhitespace{$this->id} ( s ) {
                var i;
                if ( isEmpty{$this->id}( s ) ) return true;
                for ( i=0; i<s.length; i++ ) {
                    var c = s.charAt( i );
                    if ( whitespace{$this->id}.indexOf( c ) == -1 ) return false;
                }
                return true;
            }
        JS;

        $this->existsJS = <<<JS
            function doesExist{$this->id} ( s ) {
                return ( ! isEmpty{$this->id}( s ) && ! isWhitespace{$this->id} ( s ) );
            }

        JS;

        $this->emailJS = <<<JS
            function isEmail{$this->id} ( s ) {
                if ( isEmpty{$this->id}( s ) ) return true;
                if ( isWhitespace{$this->id}( s ) ) return false;
                var i = 1;
                var sLength = s.length;
                while ( ( i < sLength ) && ( s.charAt( i ) != "@" ) ) i++;
                if ( ( i >= sLength ) || ( s.charAt( i ) != "@" ) ) return false;
                else i += 2;
                while ( ( i < sLength ) && ( s.charAt( i ) != "." ) ) i++;
                if ( ( i >= sLength - 1 ) || ( s.charAt( i ) != "." ) ) return false;
                else return true;
            }
        JS;

        $this->integerJS = <<<JS
            function isDigit{$this->id}( num ) {
                if ( num.length > 1 ) return false;
                var string = "1234567890";
                if ( string.indexOf( num ) != -1) return true;
                return false;
            }
            function isInteger{$this->id}( val ) {
                var ok;
                for ( var i=0; i<val.length; i++ ) {
                    var ch = val.charAt( i );
                    if ( ( i == 0 ) && ( ch == '+' || ch == '-' ) ) ok = true;
                    else if ( isDigit{$this->id} ( ch ) ) ok = true;
                    else {
                        return false;
                    }
                }
                return true;
            }
        JS;

        $this->validateHeaderJS = <<<JS
            function validateForm{$this->id}() {
                var formName='%s';
        JS;

        $this->testCaseHeaderJS = <<<JS
                var fieldName = '%s';
                var element = jQuery('form[name=' + formName + '] input[name=' + fieldName + ']');
                if ( element.length == 1 ) {
        JS;

        $this->caseExistsJS = <<<JS
                if ( ! doesExist{$this->id} ( element.val() ) ) {
                    alert ( "%s" );
                    element.focus();
                    return false;
                }
        JS;

        $this->caseLenEqJS = <<<JS
                if ( ! ( ( element.val().length == 0 && %s ) || element.val().length == %s ) ) {
                    alert ( "%s" );
                    element.focus();
                    return false;
                }
        JS;

        $this->caseLenBetweenJS = <<<JS
                if ( ! ( ( element.val().length == 0 && %s ) || ( element.val().length >= %s && element.val().length <= %s ) ) ) {
                    alert ( "%s" );
                    element.focus();
                    return false;
                }
        JS;

        $this->caseLenGTJS = <<<JS
                if ( ! ( ( element.val().length == 0 && %s ) || element.val().length >= %s ) ) {
                    alert ( "%s" );
                    element.focus();
                    return false;
                }
        JS;

        $this->caseLenLTJS = <<<JS
                if ( ! ( ( element.val().length == 0 && %s ) || element.val().length <= %s ) ) {
                    alert ( "%s" );
                    element.focus();
                    return false;
                }
        JS;

        $this->caseIntegerJS = <<<JS
                if ( ! isInteger{$this->id} ( element.val() ) ) {
                    alert ( "%s" );
                    element.focus();
                    return false ;
                }
        JS;

        $this->caseRangeBetweenJS = <<<JS
                if ( ! isEmpty{$this->id} ( element.val() ) ) {
                    if ( parseInt ( element.val() ) < %s || parseInt ( element.val() ) > %s ) {
                        alert ( "%s" );
                        element.focus();
                        return false;
                    }
                }
        JS;

        $this->caseRangeGTJS = <<<JS
                if ( ! isEmpty{$this->id} ( element.val() ) ) {
                    if ( parseInt ( element.val() ) < %s ) {
                        alert ( "%s" );
                        element.focus();
                        return ( false );
                    }
                }
        JS;

        $this->caseRangeLTJS = <<<JS
                if ( ! isEmpty{$this->id} ( element.val() ) ) {
                    if ( parseInt ( element.val() ) > %s ) {
                        alert ( "%s" );
                        element.focus();
                        return false;
                    }
                }
        JS;

        $this->caseEmailJS = <<<JS
                if ( ! isEmail{$this->id} ( element.val() ) ) {
                    alert ( "%s" );
                    element.focus();
                    return false;
                }
        JS;

        $this->caseEqualJS = <<<JS
                var fieldName2='%s';
                var element2 = jQuery('form[name=' + formName + '] input[name=' + fieldName2 + ']');
                if ( element.val() != element2.val() ) {
                    alert ( "%s" );
                    element.focus();
                    return false;
                }
        JS;

        $this->caseCopyJS = <<<JS
                var fieldName2 = '%s';
                var element2 = jQuery('form[name=' + formName + '] input[name=' + fieldName2 + ']');
                if ( ! doesExist{$this->id} ( element2.val() ) ) {
                    element2.val( element.val() );
                }
        JS;

        $this->validateFooterJS = <<<JS
            }
        JS;

        $this->caseAlertMissingJS = <<<JS
            else {
                alert ( "Form variable '%s' does not exist in this form" );
                return false;
            }
        JS;

        $this->validateFunctionJS = <<<JS
                return true;
            }
        JS;

        $this->case24HrTimeJS = <<<JS
                if ( ! is24HrTime{$this->id} ( element.val() ) ) {
                    alert ( "%s" );
                    if ( "%s" != "" ) element.val ( "%s" );
                    element.focus();
                    return false;
                }
        JS;

        $this->t24HrTimeJS = <<<JS
            function is24HrTime{$this->id} ( s ) {
                s = $.trim ( s );
                if ( s.length == 0 ) return true;
                if ( s.length != 5 ) return false;
                var parts = s.split ( ':' );
                if ( parts.length != 2 ) return false;
                if ( ! isInteger{$this->id} ( parts[0] ) ) return false;
                if ( ! isInteger{$this->id} ( parts[1] ) ) return false;
                var hrs = parseInt ( parts[0] );
                var min = parseInt ( parts[1] );
                if ( hrs < 0 || hrs > 23 || min < 0 || min > 60 ) return false;
                return true;
            }
        JS;

        $this->caseMySQLDateTimeJS = <<<JS
                if ( ! isEmpty{$this->id}( element.val() ) ) {
                    if ( isNaN( Date.parse( element.val().substring(0, 10) + "T" + element.val().substring(11) ) ) ) {
                        alert ( "%s" );
                        element.focus();
                        return false;
                    }
                }
        JS;
    }
}
