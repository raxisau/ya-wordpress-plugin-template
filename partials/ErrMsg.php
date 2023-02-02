<?php
namespace App;

class ErrMsg extends \Jackbooted\Util\JB {
    //<body><pre>
    // ------ Edit below line only.
    const FORM_MISSING  = 'Form variable %s cannot be empty, please re-enter';
    const EMAIL_FORMAT  = 'The email (%s) is not valid format';
    const PAN           = 'Invalid Payment Card number, please re-enter';
    const EXP           = 'Invalid Payment Card Expiry Date, please re-enter';
    const CVV           = 'Invalid Payment Card CVV Number, please re-enter';
    const PHONE         = 'Invalid phone number %s';
    const LASTNAME      = 'Last name is missing. Please enter a full name';
    // ------ Edit Above line only.
    //</body>
}

