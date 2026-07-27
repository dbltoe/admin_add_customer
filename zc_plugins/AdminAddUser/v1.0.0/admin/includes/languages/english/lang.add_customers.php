<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://zen-cart.com GNU Public License V2.0
 * @version $Id: lang.add_customers.php 2026-07-26 05:30:22Z dbltoe $
 */
// -----
// The original DIR_WS_LANGUAGES-relative require assumed the admin root as the working
// directory, which the encapsulated plugin language loader does not guarantee, and this
// admin install may not ship its own copy of gv_name.php at all (some Zen Cart 2.x admins
// only carry it on the storefront side). Try the admin copy, then the storefront copy (both
// via absolute filesystem paths so resolution doesn't depend on the loader's CWD), and fall
// back to sane defaults so this page never fatals over optional gift-voucher terminology.
//
if (!defined('TEXT_GV_NAME')) {
    $acfa_gv_name_file = $_SESSION['language'] . '/gv_name.php';
    if (defined('DIR_FS_ADMIN') && is_file(DIR_FS_ADMIN . 'includes/languages/' . $acfa_gv_name_file)) {
        require DIR_FS_ADMIN . 'includes/languages/' . $acfa_gv_name_file;
    } elseif (defined('DIR_FS_CATALOG') && is_file(DIR_FS_CATALOG . 'includes/languages/' . $acfa_gv_name_file)) {
        require DIR_FS_CATALOG . 'includes/languages/' . $acfa_gv_name_file;
    } else {
        define('TEXT_GV_NAME', 'Gift Certificate');
        define('TEXT_GV_REDEEM', 'Redemption Code');
    }
    unset($acfa_gv_name_file);
}

// -----
// These two are referenced from within ENTRY_PHONE_NO_MIN_ERROR/MAX_ERROR below, so they must
// exist as real constants before the returned array is built (array values are evaluated
// immediately, before the loader gets a chance to define() any of the returned entries).
//
if (!defined('ENTRY_PHONE_NO_MIN_DIGITS')) {
    define('ENTRY_PHONE_NO_MIN_DIGITS', '10');
}
if (!defined('ENTRY_PHONE_NO_MAX_DIGITS')) {
    define('ENTRY_PHONE_NO_MAX_DIGITS', '15');
}

$define = [
    'HEADING_TITLE' => 'Add Customer(s)',

    'TYPE_BELOW' => 'Type below',
    'PLEASE_SELECT' => 'Select One',
    'CUSTOMERS_REFERRAL' => 'Customer Referral<br>1st Discount Coupon',

    'ENTRY_NONE' => 'None',

    'TABLE_HEADING_COMPANY' => 'Company',

    'EMAIL_CUSTOMER_STATUS_CHANGE_MESSAGE' => 'Your customer status has been updated. Thank you for shopping with us. We look forward to your business.',
    'EMAIL_CUSTOMER_STATUS_CHANGE_SUBJECT' => 'Customer Status Updated',

    // -----
    // Options section labels on the single-entry form. ENTRY_DISCOUNT_PRICING_GROUP is a
    // plugin-owned label rather than reusing core's own ENTRY_PRICING_GROUP constant, so its
    // wording (including punctuation) can be set here without affecting core's own
    // admin/customers.php screen, which shares that constant.
    //
    'ENTRY_DISCOUNT_PRICING_GROUP' => 'Discount Pricing Group:',

    // -----
    // Wholesale level field on the single-entry form. Zen Cart core defines no fixed list of
    // what each level means (%, $, or otherwise) - that's entirely up to the store's own
    // pricing setup, so this is just a plain number matching whatever scheme is already in use.
    //
    'ENTRY_WHOLESALE' => 'Wholesale Pricing:',
    'HELPTEXT_WHOLESALE' => 'Enter 0 for none, or a number matching the wholesale settings already used in your store.',

    // -----
    // Only shown alongside Wholesale Pricing. This is now the only place a tax/resale number
    // can be entered at all - the catalog completion page that used to also collect it was
    // dropped in favor of activating immediately and sending the customer to log in and use
    // Zen Cart's own account pages.
    //
    'ENTRY_TAX_NUMBER' => 'Tax / Resale Number:',

    // greeting salutation
    'EMAIL_SUBJECT' => 'Welcome to ' . STORE_NAME,
    'EMAIL_GREET_MR' => 'Dear Mr. %s,' . "\n\n",
    'EMAIL_GREET_MS' => 'Dear Ms. %s,' . "\n\n",
    'EMAIL_GREET_NONE' => 'Dear %s' . "\n\n",

    // First line of the greeting
    'EMAIL_WELCOME' => 'We wish to welcome you to <strong>' . STORE_NAME . '</strong>.',
    'EMAIL_SEPARATOR' => '--------------------',

    // -----
    // Summary of what was assigned to this customer (group/wholesale/coupon), and the
    // account-activation link. See addCustomers::sendWelcomeEmail().
    //
    'EMAIL_SUMMARY_HEADER' => 'Here is a summary of what has been set up for your new account:',
    'EMAIL_SUMMARY_GROUP' => 'Pricing Group: %s',
    // -----
    // Builds a plain-language label from the group's raw name/percentage, e.g.
    // "Group 10 - 10% Off Retail" - mirrors catalog/includes/languages/english/
    // lang.admin_add_user_activate.php's TEXT_GROUP_DISCOUNT_LABEL (admin and catalog each
    // load their own separate language files, so this can't just reference that one).
    //
    'EMAIL_GROUP_DISCOUNT_LABEL' => '%s - %d%% Off Retail',
    // -----
    // Deliberately doesn't show the raw wholesale level number - that's an internal pricing
    // detail with no customer-facing meaning, and showing it invited "how do I get to level
    // #?" questions. This line only appears at all when the level is > 0, so a fixed
    // "Activated" is all that's needed.
    //
    'EMAIL_SUMMARY_WHOLESALE' => 'Wholesale Level: Activated',
    'EMAIL_SUMMARY_COUPON' => 'Coupon assigned: %s',
    'EMAIL_ACTIVATION_HEADER' => 'Your account is not yet active. To activate it, please click the link below:',

    'EMAIL_GV_INCENTIVE_HEADER' => 'Just for stopping by today, we have sent you a ' . TEXT_GV_NAME . ' for %s!' . "\n",
    'EMAIL_GV_REDEEM' => 'The ' . TEXT_GV_NAME . ' ' . TEXT_GV_REDEEM . ' is: %s ' . "\n\n" . 'You can enter the ' . TEXT_GV_REDEEM . ' during Checkout, after making your selections in the store. ',
    'EMAIL_GV_LINK' => ' Or, you may redeem it now by following this link: ' . "\n",
    // GV link will automatically be included before this line

    'EMAIL_GV_LINK_OTHER' => 'Once you have added the ' . TEXT_GV_NAME . ' to your account, you may use the ' . TEXT_GV_NAME . ' for yourself, or send it to a friend!' . "\n\n",

    'EMAIL_TEXT_1' => 'Your login ID is the e-mail address at which you received this message.' . "\n\n",
    'EMAIL_TEXT_2' => 'Your new password is: %s ' . "\n\n",
    'EMAIL_TEXT_3' => 'With your account, you can now take part in the <strong>various services</strong> we have to offer you. Some of these services include:' . "\n\n" . '<li><strong>Permanent Cart</strong> - Any products added to your online cart remain there until you remove them, or check them out.' . "\n\n" . '<li><strong>Address Book</strong> - We can now deliver your products to another address other than yours! This is perfect to send birthday gifts direct to the birthday-person themselves.' . "\n\n" . '<li><strong>Order History</strong> - View your history of purchases that you have made with us.' . "\n\n" . '<li><strong>Products Reviews</strong> - Share your opinions on products with our other customers.' . "\n\n",
    'EMAIL_CONTACT' => 'For help with any of our online services, please email the store-owner: <a href="mailto:' . STORE_OWNER_EMAIL_ADDRESS . '">' . STORE_OWNER_EMAIL_ADDRESS . " </a>\n\n",
    'EMAIL_GV_CLOSURE' => 'Sincerely,' . "\n\n" . STORE_OWNER . "\nStore Owner\n\n" . '<a href="' . zen_catalog_href_link(FILENAME_DEFAULT) . '">' . zen_catalog_href_link(FILENAME_DEFAULT) . "</a>\n\n",

    // email disclaimer - this disclaimer is separate from all other email disclaimers
    'EMAIL_DISCLAIMER_NEW_CUSTOMER' => 'This email address was given to us by you or by one of our customers. If you did not signup for an account, or feel that you have received this email in error, please send an email to %s ',

    'ERROR_CUSTOMER_ERROR_1' => 'There were errors',
    'ERROR_CUSTOMER_EXISTS' => 'Customers exist: ',
    'CUSTOMERS_BULK_UPLOAD' => 'Bulk Upload (CSV)',
    'CUSTOMERS_FILE_IMPORT' => 'File to Import: ',
    'TEXT_FULL_NAME' => '(Full State)',
    'CUSTOMERS_ONE_FORMS' => 'Click here to see the Single Customer form',

    // -----
    // Bulk-area downloads: blank CSV template, and links to the two static, store-owner-
    // supplied PDF sign-up forms in this plugin's own pdf/ folder (Zen Cart core ships no
    // PDF-generation library, so these aren't generated by this plugin).
    //
    'DOWNLOAD_CSV_TEMPLATE' => 'Download Blank CSV Template',
    'PRINT_SIGNUP_FORM' => 'Print Sign-Up Form',
    'PRINT_SIGNUP_FORM_WHOLESALE' => 'Print Sign-Up Form (with Wholesale option)',
    'ERROR_PRINT_FORM_MISSING' => 'The file "%s" was not found in this plugin\'s pdf/ folder. Add it there to enable this download.',

    // -----
    // Optional header banner image for the welcome email's HTML version - see
    // addCustomers::uploadEmailHeaderImage() and email/README.txt.
    //
    'EMAIL_HEADER_IMAGE_TITLE' => 'Welcome Email Header Image',
    'HELPTEXT_EMAIL_HEADER_IMAGE' => 'Optional. Shown at the top of the welcome/activation email\'s HTML version, matching Zen Cart\'s own default email header size of 550 x 110 (not required or enforced - larger or smaller images are simply scaled to fit). Uploading a new image replaces any existing one.',
    'TEXT_CURRENT_HEADER_IMAGE' => 'Current image:',
    'ALT_CURRENT_HEADER_IMAGE' => 'Current welcome email header image',
    'TEXT_EMAIL_HEADER_IMPORT' => 'Email Header to Import: ',
    'MESSAGE_EMAIL_HEADER_UPLOADED' => 'The email header image was uploaded successfully.',
    'ERROR_NO_HEADER_IMAGE_FILE' => 'Please choose an image file before pressing "Upload".',
    'ERROR_BAD_HEADER_IMAGE_EXTENSION' => 'The file extension (%s) must be one of: ',
    'ERROR_NOT_A_VALID_IMAGE' => 'The uploaded file does not appear to be a valid image.',
    'ERROR_HEADER_IMAGE_DIR_MISSING' => 'This plugin\'s email/ folder is missing - reinstalling the plugin should restore it.',

    'ERROR_ON_LINE' => 'Errors on line %u of the imported file',
    'MESSAGE_CUSTOMERS_OK' => '%u customers were successfully inserted.',
    'MESSAGE_LINES_OK_NOT_INSERTED' => 'The following lines were validated but, due to errors in other records, were not inserted into the database.',
    'MESSAGE_CUSTOMER_OK' => 'The customer (%s) was inserted successfully.',
    'LINE_MSG' => 'Line %u (%s %s)',
    'FORMATTING_THE_CSV' => 'Formatting the CSV',
    // -----
    // Accessible name for the CSV help icon-link - the icon itself is aria-hidden, so this is
    // the link's only source of an accessible name. A plain title attribute isn't a reliable
    // accessible-name source on its own (inconsistent across screen readers and unavailable on
    // touch), so it's used as both the title and an explicit aria-label.
    //
    'TEXT_HELP_CSV_FORMAT' => 'View CSV formatting help (opens in a new window)',
    'CUSTOMERS_SINGLE_ENTRY' => 'Single Customer Entry',
    'DATE_FORMAT_CHOOSE_SINGLE' => 'Date of Birth Format: ',
    'RESEND_WELCOME_EMAIL' => 'Resend the Welcome E-Mail',
    'BUTTON_RESEND' => 'Resend',
    'TEXT_PLEASE_CHOOSE' => 'Please Choose',
    'TEXT_CHOOSE_CUSTOMER' => 'Choose a Customer: ',
    'TEXT_RESET_PASSWORD' => 'Reset the Customer\'s Password?',
    'CUSTOMER_EMAIL_RESENT' => 'The "Welcome E-Mail" was re-sent to the customer.',

    // -----
    // Permanently deletes customers who registered before a chosen date and never got as far
    // as having a complete address on file - see addCustomers::deleteAbandonedSignups().
    //
    'DELETE_ABANDONED_SIGNUPS' => 'Delete Unclaimed Signups',
    'HELPTEXT_DELETE_ABANDONED' => 'Permanently deletes customers registered before the chosen date who never entered a complete address - i.e. never activated at all, or activated but never finished logging in and completing their profile. This cannot be undone.',
    'TEXT_DELETE_BEFORE_DATE' => 'Registered Before: ',
    'BUTTON_DELETE_ABANDONED' => 'Delete',
    'TEXT_DELETE_ABANDONED_CONFIRM' => 'Permanently delete all customers with no address on file who registered before the selected date? This cannot be undone.',
    'MESSAGE_ABANDONED_DELETED' => '%u unclaimed signup(s) were permanently deleted.',
    'ERROR_INVALID_DELETE_DATE' => 'Please choose a valid date.',

    // Configuration and messages for the phone_validate function
    'ENTRY_PHONE_NO_DELIMS' => '-. ()',
    'ENTRY_PHONE_NO_DELIM_WORLD' => '+',  // Set to false if you don't support world phone numbers
    'ENTRY_PHONE_NO_CHAR_ERROR' => 'There is an invalid character (%s) in the "Telephone Number".',
    'ENTRY_PHONE_NO_MIN_ERROR' => 'There are fewer than ' . ENTRY_PHONE_NO_MIN_DIGITS . ' digits (0-9) in the "Telephone Number".',
    'ENTRY_PHONE_NO_MAX_ERROR' => 'There are more than ' . ENTRY_PHONE_NO_MAX_DIGITS . ' digits (0-9) in the "Telephone Number".',

    'ERROR_NO_UPLOAD_FILE' => 'Please choose a "File to Import" before pressing "Upload"',
    'ERROR_FILE_UPLOAD' => 'Error (%s) uploading file',
    'ERROR_BAD_FILE_EXTENSION' => 'The file extension (%s) must be one of: ',
    'ERROR_BAD_FILE_HEADER' => 'Either the header row in the input file is empty or it was not recognised.',
    'ERROR_NO_RECORDS' => 'No customer records were found in the imported file.',  /*v2.0.3a*/
    'ERROR_FIRST_NAME' => '"First Name" must be at least ' . ENTRY_FIRST_NAME_MIN_LENGTH . ' characters in length.',
    'ERROR_LAST_NAME' => '"Last Name" must be at least ' . ENTRY_LAST_NAME_MIN_LENGTH . ' characters in length.',
    'ERROR_EMAIL_LENGTH' => '"E-Mail Address" must be at least ' . ENTRY_EMAIL_ADDRESS_MIN_LENGTH . ' characters in length.',
    'ERROR_EMAIL_INVALID' => 'The "E-Mail Address" format is not valid.',
    'ERROR_EMAIL_ADDRESS_ERROR_EXISTS' => 'The "E-Mail Address" (%s) already exists.',
    'ERROR_STREET_ADDRESS' => '"Street Address" must be at least ' . ENTRY_STREET_ADDRESS_MIN_LENGTH . ' characters in length.',
    'ERROR_CITY' => '"City" must be at least ' . ENTRY_CITY_MIN_LENGTH . ' characters in length.',
    'ERROR_POSTCODE' => '"Post Code" must be at least ' . ENTRY_POSTCODE_MIN_LENGTH . ' characters in length.',
    'ENTRY_POSTCODE_NOT_VALID' => 'The "Post Code" (%s) is not valid for %s.',
    'ERROR_COUNTRY' => 'Please select a "Country"',
    'ERROR_STATE_REQUIRED' => '"State" is required for the currently selected "Country".',
    'ERROR_SELECT_STATE' => 'Please select a "State".',
    'ERROR_CANT_MOVE_FILE' => 'Could not move file, check folder permissions.',
    'ERROR_NO_CUSTOMER_SELECTED' => 'Please select a customer before you press "Resend".',
    'ERROR_UNKNOWN_GROUP_PRICING' => 'Unknown "Customer Group Pricing" value (%u).',
    'ERROR_MISSING_CREATE_ACCOUNT_COUPON' => 'The create-account coupon (%s) is not valid; it has not been included in the customer welcome email.',
];

return $define;
