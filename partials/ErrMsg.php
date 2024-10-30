<?php
namespace App;

class ErrMsg extends \Jackbooted\Util\JB {
    //<body><pre>
    // ------ Edit below line only.
    const API1             = 'Please report to Support, API Error(%s): %s';
    const API2             = 'Please report to Support, API Error(%s)';
    const LEAD_LOOKUP      = 'No Reference number has been passed';
    const LEAD_MISSING     = 'Was unable to locate object for reference number %s';
    const FORM_MISSING     = 'Form variable %s cannot be empty, please re-enter';
    const DOMAIN_FORMAT    = 'The domain name (%s) is not valid format.';
    const EMAIL_FORMAT     = 'The email (%s) is not valid format';
    const PAN              = 'Invalid Payment Card number, please re-enter';
    const EXP              = 'Invalid Payment Card Expiry Date, please re-enter';
    const CVV              = 'Invalid Payment Card CVV Number, please re-enter';
    const PHONE            = 'Invalid phone number %s';
    const LASTNAME         = 'Last name is missing. Please enter a full name';
    const ACN_MISSING      = 'The ABN/ACN is missing. You MUST supply a valid ABN/ACN';
    const ACN_INVALID      = 'You have entered an invalid ABN. Please enter active ABN/ACN and try again.';
    const DOM_REDIR_TITLE  = 'Connecting you to your drop catching account.';
    const DOM_REDIR_MSG    = 'Please click here if your browser does not redirect.';
    const CUST_MSG         = 'You are already a Drop Catcher customer and the domain is available. Please follow the link to signin and register the domain.';
    const CUST_TITLE       = 'Already a customer.';
    const HOST_REDIR_MSG   = 'Please click here if your browser does not redirect.';
    const HOST_REDIR_TITLE = 'Connecting you to your registration account.';
    const EXIST_CUST_TITLE = 'System Error.';
    const EXIST_CUST_MSG   = 'This feature is unavailable at the moment. Please contact <a href="/contact">support</a>.';
    const PAGE_EXPIRED     = 'This page has expired. Please contact <a href="/contact">support</a> if you need more assistance.';
    const EXPIRED_TITLE    = 'Page Expired.';
    // ------ Edit Above line only.
    //</body>
}

