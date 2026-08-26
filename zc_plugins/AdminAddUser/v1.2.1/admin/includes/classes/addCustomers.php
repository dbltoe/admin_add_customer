<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://zen-cart.com GNU Public License V2.0
 * @version $Id: addCustomers.php 2026-08-26 15:49:10Z dbltoe $
 */
// -----
// This file is only ever loaded by admin/add_customers.php, after application_top.php has run.
// Zen Cart's shipped zc_plugins/.htaccess already denies direct HTTP requests to .php files,
// but that's Apache-only and depends on AllowOverride being permitted - it does nothing on
// nginx/LiteSpeed or a locked-down shared host. This is the same guard core uses on its own
// included admin files (and that lat9's POSM uses on its plugin admin classes), so a direct
// request can't execute this file even where the .htaccess isn't honored. NOTE: the guard
// belongs on *included* files only - a top-level admin page like add_customers.php must not
// have it, since IS_ADMIN_FLAG doesn't exist until the application_top.php it requires has run.
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

class addCustomers extends base
{
    // -----
    // Mirrors core's own Customer::AUTH_OK / Customer::AUTH_NO_PURCHASE values. Not read from
    // core (that class isn't guaranteed present pre-1.5.8), just the same well-established
    // customers_authorization column values this mod has always worked with.
    //
    protected const AUTH_OK = 0;
    protected const AUTH_NO_PURCHASE = 3;

    // -----
    // The recognized CSV bulk-upload columns. Shared between checkFileUpload() (which parses
    // an uploaded file against this list) and getCsvTemplateContents() (which generates a
    // blank file using this same list), so the two can't drift apart. This is deliberately the
    // bare minimum to get someone started - name, email, and (optionally) phone, plus the two
    // account-assignment fields (Group, Wholesale). Address, gender, DOB, company, etc. are
    // never collected here at all - the customer enters those themselves, via Zen Cart's own
    // account/address-book pages once they log in after clicking their activation link.
    //
    protected const CSV_HEADERS = [
        'first_name', 'last_name', 'email', 'telephone', 'customers_group_pricing', 'wholesale_level',
    ];

    protected array $headers;
    protected bool $sendWelcomeEmailAddressCouponError;
    protected int $customers_inserted;

    public function __construct()
    {
        $this->headers = [];
        $this->sendWelcomeEmailAddressCouponError = false;
        $this->customers_inserted = 0;
    }

    // -----
    // Helper methods to retrieve the class-based variables.
    //
    public function getCustomersInserted()
    {
        return $this->customers_inserted;
    }

    // -----
    // Whether the store has Wholesale Pricing enabled at all (core setting, since v2.0.0);
    // used to decide whether to show the Wholesale Level field.
    //
    // Deliberately not zen_config() - that helper is @since ZC v3.0.0 only, so calling it
    // fatals with "Call to undefined function" on any earlier version, confirmed by a real
    // user report on v2.1.0/PHP 8.2.1. defined()/constant() is the same fallback zen_config()
    // itself uses internally when its own backing repository isn't available, so this behaves
    // identically on every version this plugin claims to support, not just the ones where
    // zen_config() happens to exist.
    //
    public function wholesaleEnabled()
    {
        return (defined('WHOLESALE_PRICING_CONFIG') ? constant('WHOLESALE_PRICING_CONFIG') : 'false') !== 'false';
    }

    // -----
    // Active coupons dropdown. Not currently wired into any form - coupon assignment was
    // removed from both entry points (2026) so the coupon is decided later, if at all, rather
    // than adding another field to fill in at signup time. Kept here for the planned "assign a
    // coupon" option on the Resend Welcome E-Mail tool, which would reuse this unchanged.
    // Coupons with a defined expiry in the past are excluded; everything else (including
    // coupons with no expiry) is offered, leaving actual usage-limit enforcement to the
    // coupon's own existing settings.
    //
    public function getActiveCouponsDropdown()
    {
        global $db;

        $coupons = [
            ['id' => '0', 'text' => TEXT_NONE],
        ];

        $coupon_query = $db->Execute(
            "SELECT c.coupon_id, c.coupon_code, cd.coupon_description
               FROM " . TABLE_COUPONS . " c
                    LEFT JOIN " . TABLE_COUPONS_DESCRIPTION . " cd
                        ON cd.coupon_id = c.coupon_id
                       AND cd.language_id = " . (int)$_SESSION['languages_id'] . "
              WHERE c.coupon_active = 'Y'
                AND (c.coupon_expire_date IS NULL OR c.coupon_expire_date >= now())
              ORDER BY c.coupon_code"
        );
        foreach ($coupon_query as $next_coupon) {
            $label = $next_coupon['coupon_code'];
            if (!empty($next_coupon['coupon_description'])) {
                $label .= ' - ' . $next_coupon['coupon_description'];
            }
            $coupons[] = ['id' => $next_coupon['coupon_id'], 'text' => $label];
        }
        return $coupons;
    }

    // -----
    // A blank CSV file (header row only) matching CSV_HEADERS, for the store owner to fill in
    // and re-upload.
    //
    public function getCsvTemplateContents()
    {
        return implode(',', self::CSV_HEADERS) . "\r\n";
    }

    // -----
    // Called by the main script when the admin has submitted a bulk-import file.
    //
    public function checkFileUpload()
    {
        global $db;

        $errors = [];
        $line_num = 0;

        // -----
        // No file uploaded?  Nothing to be done ...
        //
        $files = (isset($_FILES['bulk_upload'])) ? $_FILES['bulk_upload'] : [];
        if (empty($files['name'])) {
            $errors[] = ERROR_NO_UPLOAD_FILE;
        } elseif ($files['error'] != 0) {
            $errors[] = sprintf(ERROR_FILE_UPLOAD, $files['error']);
        } else {
            $extension = pathinfo($files['name'],  PATHINFO_EXTENSION);
            $allowed_extensions = ['TXT', 'CSV'];
            if (empty($extension) || !in_array(strtoupper($extension), $allowed_extensions)) {
                $errors[] = sprintf(ERROR_BAD_FILE_EXTENSION, $extension) . implode(', ', $allowed_extensions);
            } else {
                // -----
                // Deliberately not DIR_FS_BACKUP . $files['name'] - that's the client-supplied
                // original filename, entirely attacker-controlled, used unsanitized in a
                // filesystem path. The extension allowlist above limits it to .txt/.csv, but a
                // crafted name (e.g. containing ../) could still move_uploaded_file() outside
                // DIR_FS_BACKUP or overwrite an unrelated .txt/.csv file elsewhere. This file is
                // temporary regardless - parsed immediately below and unlink()'d at the end - so
                // there's no reason to trust the original name for its on-disk path at all, the
                // same reasoning uploadEmailHeaderImage() already applies to its own upload.
                //
                $filepath = tempnam(DIR_FS_BACKUP, 'bulk_upload_');
                if ($filepath === false) {
                    $errors[] = ERROR_CANT_MOVE_FILE;
                    return $errors;
                }

                if (move_uploaded_file($files['tmp_name'], $filepath) === false) {
                    $errors[] = ERROR_CANT_MOVE_FILE;
                } else {
                    chmod($filepath, 0775);
                    ini_set('auto_detect_line_endings', true);
                    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                    $valid_headers = self::CSV_HEADERS;

                    $found_header = false;
                    foreach ($lines as $line) {
                        if (empty($line)) {
                            continue;
                        }
                        $line_num++;
                        $line = explode(',', $line);

                        // Process header row
                        if ($found_header === false) {
                            $found_header = true;
                            foreach ($line as $header_position => $header_label) {
                                $header_label = strtolower(trim($header_label));
                                if (in_array($header_label, $valid_headers)) {
                                    $this->headers[$header_label] = $header_position;
                                }
                            }
                        // Process data row
                        } elseif (count($this->headers) === 0) {
                            $errors[] = ERROR_BAD_FILE_HEADER;
                            break;
                        } else {
                            $values = [
                                'customers_firstname' => $this->getValue('first_name', $line),
                                'customers_lastname' => $this->getValue('last_name', $line),
                                'customers_email_address' => $this->getValue('email', $line),
                                'customers_telephone' => $this->getValue('telephone', $line),
                                'customers_group_pricing' => ($this->getValue('customers_group_pricing', $line) === false) ? '0' : $this->getValue('customers_group_pricing', $line),
                                'customers_whole' => ($this->getValue('wholesale_level', $line) === false) ? '0' : $this->getValue('wholesale_level', $line),
                                'customers_referral' => '',
                                'customers_email_format' => (ACCOUNT_EMAIL_PREFERENCE === '1' ? 'HTML' : 'TEXT'),
                            ];

                            list($notused, $validation_errors) = $this->validateCustomer($values);

                            // -----
                            // Each valid row is inserted immediately rather than buffered for an
                            // all-or-nothing commit at the end. With a field set this minimal
                            // (name/email, everything else optional), a bad row is almost always
                            // an isolated typo, not a sign of a systemic problem - there's no
                            // reason to hold up everyone else's activation email over one line.
                            //
                            if (!empty($validation_errors)) {
                                $errors[$line_num] = $validation_errors;
                            } else {
                                $this->insertCustomer($values);
                            }
                        }
                    }

                    if (count($errors) === 0) {
                        if ($found_header === false) {
                            $errors[] = ERROR_BAD_FILE_HEADER;
                        } elseif ($line_num === 0) {
                            $errors[] = ERROR_NO_RECORDS;
                        }
                    }

                    unlink($filepath);
                }
            }
        }
        return $errors;
    }

    protected function getValue($field_name, $line)
    {
        return (isset($this->headers[$field_name])) ? $line[$this->headers[$field_name]] : false;
    }

    // -----
    // Zen Cart v2.2.0 added `activation_required`/`welcome_email_sent` columns to the
    // customers table (v2.0.0/v2.1.0 don't have them), so their presence is checked once
    // per request and cached rather than assumed based on version.
    //
    protected function accountActivationColumnsExist()
    {
        global $db;

        static $exists = null;
        if ($exists === null) {
            $check = $db->Execute(
                "SHOW COLUMNS FROM " . TABLE_CUSTOMERS . " LIKE 'activation_required'"
            );
            $exists = !$check->EOF;
        }
        return $exists;
    }

    // -----
    // customers_tax_number is added by this plugin's own installer, but a store could be
    // mid-upgrade or the column could be missing for other reasons, so this is checked once
    // per request and cached, the same way accountActivationColumnsExist() is.
    //
    protected function taxNumberColumnExists()
    {
        global $db;

        static $exists = null;
        if ($exists === null) {
            $check = $db->Execute(
                "SHOW COLUMNS FROM " . TABLE_CUSTOMERS . " LIKE 'customers_tax_number'"
            );
            $exists = !$check->EOF;
        }
        return $exists;
    }

    // -----
    // Allowed extensions for the welcome email's optional header banner image, in the priority
    // order checked when more than one happens to be present.
    //
    protected const HEADER_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    protected function getEmailImageDir()
    {
        return dirname(__DIR__, 3) . '/email/';
    }

    // -----
    // Optional store-supplied header banner image for the welcome email (HTML version only) -
    // see email/README.txt, and uploadEmailHeaderImage() below for the admin-page upload
    // route. Not something this plugin generates; only used if the store owner has actually
    // supplied one. Checked once per request and cached, same pattern as the two
    // column-existence checks above.
    //
    public function getHeaderImageUrl()
    {
        static $url = null;

        if ($url === null) {
            $url = '';
            $emailDir = $this->getEmailImageDir();
            foreach (self::HEADER_IMAGE_EXTENSIONS as $extension) {
                if (is_file($emailDir . 'header.' . $extension)) {
                    // -----
                    // Derived from the actual on-disk folder names (rather than hardcoded)
                    // so this doesn't need a manual update on every version bump.
                    //
                    $pluginPath = basename(dirname(__DIR__, 4)) . '/' . basename(dirname(__DIR__, 3));
                    $url = HTTP_SERVER . DIR_WS_CATALOG . 'zc_plugins/' . $pluginPath . '/email/header.' . $extension;
                    break;
                }
            }
        }

        return $url;
    }

    // -----
    // Handles an upload from the admin page's "Email Header to Import:" field. Any existing
    // header.* files are removed first so exactly one is ever present at a time (otherwise an
    // old file in a different format could linger and still win, per
    // HEADER_IMAGE_EXTENSIONS's priority order, even after uploading a replacement). The saved
    // filename is always header.<extension> - the uploaded file's original name is never used
    // for anything, including on disk.
    //
    public function uploadEmailHeaderImage()
    {
        $errors = [];

        $file = (isset($_FILES['email_header_image'])) ? $_FILES['email_header_image'] : [];
        if (empty($file['name'])) {
            $errors[] = ERROR_NO_HEADER_IMAGE_FILE;
            return $errors;
        }
        if ($file['error'] != 0) {
            $errors[] = sprintf(ERROR_FILE_UPLOAD, $file['error']);
            return $errors;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::HEADER_IMAGE_EXTENSIONS, true)) {
            $errors[] = sprintf(ERROR_BAD_HEADER_IMAGE_EXTENSION, $extension) . implode(', ', self::HEADER_IMAGE_EXTENSIONS);
            return $errors;
        }

        if (@getimagesize($file['tmp_name']) === false) {
            $errors[] = ERROR_NOT_A_VALID_IMAGE;
            return $errors;
        }

        $emailDir = $this->getEmailImageDir();
        if (!is_dir($emailDir)) {
            $errors[] = ERROR_HEADER_IMAGE_DIR_MISSING;
            return $errors;
        }

        foreach (self::HEADER_IMAGE_EXTENSIONS as $existingExtension) {
            $existingFile = $emailDir . 'header.' . $existingExtension;
            if (is_file($existingFile)) {
                unlink($existingFile);
            }
        }

        if (move_uploaded_file($file['tmp_name'], $emailDir . 'header.' . $extension) === false) {
            $errors[] = ERROR_CANT_MOVE_FILE;
        }

        return $errors;
    }

    // -----
    // Creates (and stores) a fresh activation token for a just-created or resent customer.
    // $coupon_id is stored alongside the token so the catalog-side activation page can include
    // the same assigned coupon in its summary without having to re-derive it from anywhere
    // else.
    //
    protected function createActivationToken($customers_id, $coupon_id = 0)
    {
        global $db;

        $token = bin2hex(random_bytes(32));

        $sql_data_array = [
            'customers_id' => (int)$customers_id,
            'token' => $token,
            // -----
            // zen_db_perform() casts values to string and only treats the literal strings
            // 'NULL'/'null' as a real SQL NULL - a PHP null here would otherwise become an
            // empty string.
            //
            'coupon_id' => ($coupon_id > 0) ? (int)$coupon_id : 'null',
        ];
        zen_db_perform(TABLE_ADMIN_ADD_USER_TOKENS, $sql_data_array);

        return $token;
    }

    // -----
    // Called by the admin's "Resend the Welcome E-Mail" tool. Replaces any existing token for
    // the customer (carrying forward the previously-assigned coupon, if any) with a fresh one,
    // optionally resets the password, and re-sends the welcome/activation email.
    //
    public function regenerateAndResendToken($customers_id, $reset_password = false)
    {
        global $db;

        $customers_id = (int)$customers_id;

        $existing_token = $db->Execute(
            "SELECT coupon_id
               FROM " . TABLE_ADMIN_ADD_USER_TOKENS . "
              WHERE customers_id = " . $customers_id . "
              LIMIT 1"
        );
        $coupon_id = (!$existing_token->EOF && $existing_token->fields['coupon_id'] !== null) ? (int)$existing_token->fields['coupon_id'] : 0;

        $db->Execute(
            "DELETE FROM " . TABLE_ADMIN_ADD_USER_TOKENS . "
              WHERE customers_id = " . $customers_id
        );

        $thePassword = false;
        if ($reset_password === true) {
            $thePassword = zen_create_PADSS_password(((int)ENTRY_PASSWORD_MIN_LENGTH > 0) ? (int)ENTRY_PASSWORD_MIN_LENGTH : 5);
            $db->Execute(
                "UPDATE " . TABLE_CUSTOMERS . "
                    SET customers_password = '" . zen_encrypt_password($thePassword) . "'
                  WHERE customers_id = " . $customers_id . "
                  LIMIT 1"
            );
        }

        $token = $this->createActivationToken($customers_id, $coupon_id);
        $this->sendWelcomeEmail($customers_id, $token, $thePassword, $coupon_id);
    }

    // -----
    // Validates and applies the token from an emailed activation link. Returns false if the
    // token doesn't match an outstanding request; otherwise flips the customer to fully
    // authorized and consumes the token.
    //
    public function completeActivation($customers_id, $token)
    {
        global $db;

        $customers_id = (int)$customers_id;

        $check = $db->Execute(
            "SELECT token_id
               FROM " . TABLE_ADMIN_ADD_USER_TOKENS . "
              WHERE customers_id = " . $customers_id . "
                AND token = '" . zen_db_input($token) . "'
              LIMIT 1"
        );
        if ($check->EOF) {
            return false;
        }

        $db->Execute(
            "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_authorization = " . self::AUTH_OK . "
              WHERE customers_id = " . $customers_id . "
              LIMIT 1"
        );
        $db->Execute(
            "DELETE FROM " . TABLE_ADMIN_ADD_USER_TOKENS . "
              WHERE customers_id = " . $customers_id
        );

        return true;
    }

    // -----
    // Looks up a customer + their assigned token/coupon by token value alone (used by the
    // catalog landing page, which only has the token from the URL). $max_age_hours bounds how
    // old an unused token can be before it's treated as expired.
    //
    public function getPendingCustomerByToken($token, $max_age_hours = 24)
    {
        global $db;

        $result = $db->Execute(
            "SELECT t.customers_id, t.coupon_id, t.created_at, c.*
               FROM " . TABLE_ADMIN_ADD_USER_TOKENS . " t
                    INNER JOIN " . TABLE_CUSTOMERS . " c
                        ON c.customers_id = t.customers_id
              WHERE t.token = '" . zen_db_input($token) . "'
                AND t.created_at >= DATE_SUB(now(), INTERVAL " . (int)$max_age_hours . " HOUR)
              LIMIT 1"
        );
        return (!$result->EOF) ? $result->fields : false;
    }

    public function insertCustomer($info)
    {
        global $db;

        $this->customers_inserted++;

        $customers_password = zen_create_PADSS_password(((int)ENTRY_PASSWORD_MIN_LENGTH > 0) ? (int)ENTRY_PASSWORD_MIN_LENGTH : 5);

        $customers_firstname = (isset($info['customers_firstname'])) ? zen_db_prepare_input(zen_sanitize_string($info['customers_firstname'])) : '';
        $customers_lastname = (isset($info['customers_lastname'])) ? zen_db_prepare_input(zen_sanitize_string($info['customers_lastname'])) : '';
        if (!isset($info['customers_email_address'])) {
            trigger_error("insertCustomer, missing email address: " . var_export($info, true), E_USER_ERROR);
            session_write_close();
            exit();
        }
        $customers_email_address = zen_db_prepare_input($info['customers_email_address']);
        $customers_telephone = (isset($info['customers_telephone'])) ? zen_db_prepare_input($info['customers_telephone']) : '';
        $customers_fax = (isset($info['customers_fax'])) ? zen_db_prepare_input($info['customers_fax']) : '';
        $customers_newsletter = (isset($info['customers_newsletter'])) ? zen_db_prepare_input($info['customers_newsletter']) : '0';
        $customers_group_pricing = (isset($info['customers_group_pricing'])) ? (int)$info['customers_group_pricing'] : 0;
        // -----
        // Wholesale Level: a plain number, 0 for none. Core defines no fixed list of what each
        // level means - that's entirely up to the store's own pricing setup - so this is just
        // whatever number the admin enters.
        //
        $customers_whole = (isset($info['customers_whole'])) ? max(0, (int)$info['customers_whole']) : 0;
        $customers_tax_number = (isset($info['customers_tax_number'])) ? zen_db_prepare_input($info['customers_tax_number']) : '';
        $coupon_id = (isset($info['coupon_id'])) ? (int)$info['coupon_id'] : 0;
        $customers_email_format = (isset($info['customers_email_format'])) ? zen_db_prepare_input($info['customers_email_format']) : ((ACCOUNT_EMAIL_PREFERENCE === '1') ? 'HTML' : 'TEXT');

        // -----
        // Customers created by this plugin always start pending; there's no admin-selectable
        // starting status any more (see the readme's "why" section). They become fully
        // authorized only by completing the emailed activation link.
        //
        $customers_authorization = self::AUTH_NO_PURCHASE;
        $customers_referral = (isset($info['customers_referral'])) ? zen_db_prepare_input($info['customers_referral']) : '';

        $registration_ip = (isset($_SERVER['REMOTE_ADDR'])) ? zen_db_prepare_input($_SERVER['REMOTE_ADDR']) : '';

        $sql_data_array = [
            'customers_firstname'     => $customers_firstname,
            'customers_lastname'      => $customers_lastname,
            'customers_email_address' => $customers_email_address,
            'customers_nick'          => '',
            'customers_telephone'     => $customers_telephone,
            'customers_fax'           => $customers_fax,
            'customers_group_pricing' => $customers_group_pricing,
            'customers_whole'         => $customers_whole,
            'customers_newsletter'    => $customers_newsletter,
            'customers_email_format'  => $customers_email_format,
            'customers_authorization' => $customers_authorization,
            'customers_password'      => zen_encrypt_password($customers_password),
            'registration_ip'         => $registration_ip,
            'last_login_ip'           => '',
        ];

        if (ACCOUNT_GENDER === 'true') {
            $sql_data_array['customers_gender'] = '';
        }
        if (ACCOUNT_DOB === 'true') {
            $sql_data_array['customers_dob'] = '0001-01-01 00:00:00';
        }
        if (CUSTOMERS_REFERRAL_STATUS === '2' && $customers_referral !== '') {
            $sql_data_array['customers_referral'] = $customers_referral;
        }

        // -----
        // Only present since Zen Cart v2.2.0. Since this plugin now always starts customers
        // pending, activation_required tracks the *same* pending state core would otherwise
        // track for its own login-gating logic - so it's set to 1 unconditionally here rather
        // than being tied to the store's CUSTOMERS_ACTIVATION_REQUIRED setting (that setting
        // is about core's own self-registration flow, which this plugin doesn't use).
        //
        if ($this->accountActivationColumnsExist()) {
            $sql_data_array['activation_required'] = 1;
            $sql_data_array['welcome_email_sent'] = 0;
        }

        if ($this->taxNumberColumnExists() && $customers_whole > 0) {
            $sql_data_array['customers_tax_number'] = $customers_tax_number;
        }

        zen_db_perform(TABLE_CUSTOMERS, $sql_data_array);
        $customer_id = $db->Insert_ID();

        // -----
        // Address is never collected at admin/CSV entry time (see the readme) - the customer
        // enters it themselves, via Zen Cart's own account/address-book pages once they log in
        // after activating. A blank default address_book row is still created here since
        // customers_default_address_id needs to point at something.
        //
        // -----
        // entry_zone_id/entry_state are populated unconditionally (not just when
        // ACCOUNT_STATE === 'true') - leaving them out of the INSERT left them at whatever the
        // address_book table's own column default is (NULL on at least one live site), which
        // core's own Customer::login() doesn't expect: it threw "Undefined array key
        // 'country_id'"/"'zone_id'" warnings trying to look up zone/country info for an address
        // row missing a real zone_id value.
        //
        $sql_data_array = [
           'customers_id'         => $customer_id,
           'entry_firstname'      => $customers_firstname,
           'entry_lastname'       => $customers_lastname,
           'entry_street_address' => '',
           'entry_postcode'       => '',
           'entry_city'           => '',
           'entry_country_id'     => 0,
           'entry_zone_id'        => '0',
           'entry_state'          => '',
       ];

        if (ACCOUNT_COMPANY === 'true') {
            $sql_data_array['entry_company'] = '';
        }
        if (ACCOUNT_SUBURB === 'true') {
            $sql_data_array['entry_suburb'] = '';
        }
        zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
        $address_id = $db->Insert_ID();

        $db->Execute(
            "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_default_address_id = " . $address_id . "
              WHERE customers_id = " . $customer_id . "
              LIMIT 1"
        );
        $db->Execute(
            "INSERT INTO " . TABLE_CUSTOMERS_INFO . "
                (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created)
             VALUES
                (" . $customer_id . ", 0, now())"
        );

        // -----
        // The welcome/activation email is no longer optional: it's the only way the customer
        // receives both their auto-generated password and the link that activates their
        // account, so it's always sent.
        //
        $token = $this->createActivationToken($customer_id, $coupon_id);
        $this->sendWelcomeEmail($customer_id, $token, $customers_password, $coupon_id);
        if ($this->accountActivationColumnsExist()) {
            $db->Execute(
                "UPDATE " . TABLE_CUSTOMERS . "
                    SET welcome_email_sent = 1
                  WHERE customers_id = " . $customer_id . "
                  LIMIT 1"
            );
        }

        return $customers_firstname . ' ' . $customers_lastname;
    }

    // -----
    // $customers_id/$token/$coupon_id drive the lookups this method needs (group, wholesale
    // tier, coupon) and build the activation link; $customers_password is the plaintext
    // password to include (false to omit, e.g. a resend that didn't reset it).
    //
    public function sendWelcomeEmail($customers_id, $token, $customers_password, $coupon_id = 0)
    {
        global $db, $currencies, $messageStack;

        $customers_id = (int)$customers_id;

        $customer_query = $db->Execute(
            "SELECT customers_gender, customers_firstname, customers_lastname, customers_email_address,
                    customers_group_pricing, customers_whole
               FROM " . TABLE_CUSTOMERS . "
              WHERE customers_id = " . $customers_id . "
              LIMIT 1"
        );
        if ($customer_query->EOF) {
            return;
        }
        $customers_gender = $customer_query->fields['customers_gender'];
        $customers_firstname = $customer_query->fields['customers_firstname'];
        $customers_lastname = $customer_query->fields['customers_lastname'];
        $customers_email_address = $customer_query->fields['customers_email_address'];
        $customers_group_pricing = (int)$customer_query->fields['customers_group_pricing'];
        $customers_whole = (int)$customer_query->fields['customers_whole'];

        $name = $customers_firstname . ' ' . $customers_lastname;

        if (ACCOUNT_GENDER === 'true') {
            if ($customers_gender == 'm') {
                $email_text = sprintf(EMAIL_GREET_MR, $customers_lastname);
            } else {
                $email_text = sprintf(EMAIL_GREET_MS, $customers_lastname);
            }
        } else {
            $email_text = sprintf(EMAIL_GREET_NONE, $customers_firstname);
        }

        // -----
        // Optional store-supplied header banner (HTML version only) - see email/README.txt.
        // Prepended onto EMAIL_GREETING rather than its own template token, since core's own
        // "welcome" HTML email template isn't something this plugin can add a new placeholder
        // to without editing a core file.
        //
        $headerImageUrl = $this->getHeaderImageUrl();
        $headerImageHtml = ($headerImageUrl !== '') ? '<img src="' . $headerImageUrl . '" width="550" style="max-width:100%;height:auto;display:block;" alt="' . STORE_NAME . '" />' : '';

        $html_msg['EMAIL_GREETING'] = $headerImageHtml . str_replace('\n', '', $email_text);
        $html_msg['EMAIL_FIRST_NAME'] = $customers_firstname;
        $html_msg['EMAIL_LAST_NAME']  = $customers_lastname;

        // initial welcome
        $email_text .=  EMAIL_WELCOME;
        $html_msg['EMAIL_WELCOME'] = str_replace('\n', '', EMAIL_WELCOME);

        // -----
        // Summary of what was assigned to this customer: pricing group, wholesale level, and
        // an admin-selected coupon (if any). One-time-vs-continuing use is whatever the
        // referenced coupon's own settings already say - this plugin doesn't track that
        // separately.
        //
        $summary_lines = [];
        if ($customers_group_pricing > 0) {
            $group_lookup = $db->Execute(
                "SELECT group_name, group_percentage
                   FROM " . TABLE_GROUP_PRICING . "
                  WHERE group_id = " . $customers_group_pricing . "
                  LIMIT 1"
            );
            if (!$group_lookup->EOF) {
                $group_label = sprintf(EMAIL_GROUP_DISCOUNT_LABEL, $group_lookup->fields['group_name'], (int)$group_lookup->fields['group_percentage']);
                $summary_lines[] = sprintf(EMAIL_SUMMARY_GROUP, $group_label);
            }
        }
        if ($customers_whole > 0) {
            $summary_lines[] = EMAIL_SUMMARY_WHOLESALE;
        }

        $coupon_id = (int)$coupon_id;
        if ($coupon_id > 0) {
            $coupon = $db->Execute(
                "SELECT c.coupon_code, c.coupon_type, cd.coupon_description
                   FROM " . TABLE_COUPONS . " c
                        LEFT JOIN " . TABLE_COUPONS_DESCRIPTION . " cd
                            ON cd.coupon_id = c.coupon_id
                           AND cd.language_id = " . (int)$_SESSION['languages_id'] . "
                  WHERE c.coupon_id = " . $coupon_id . "
                  LIMIT 1"
            );

            if ($coupon->EOF) {
                if ($this->sendWelcomeEmailAddressCouponError === false) {
                    $this->sendWelcomeEmailAddressCouponError = true;
                    $messageStack->add_session(sprintf(ERROR_MISSING_CREATE_ACCOUNT_COUPON, $coupon_id), 'error');
                }
            } else {
                $db->Execute(
                    "INSERT INTO " . TABLE_COUPON_EMAIL_TRACK . "
                        (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent)
                     VALUES
                        ('" . $coupon_id . "', '0', 'Admin', '" . $customers_email_address . "', now())"
                );

                $coupon_description = $coupon->fields['coupon_description'];
                $summary_lines[] = sprintf(EMAIL_SUMMARY_COUPON, $coupon->fields['coupon_code']) .
                    (!empty($coupon_description) ? ' - ' . $coupon_description : '');
                $html_msg['COUPON_CODE'] = $coupon->fields['coupon_code'];
                $html_msg['COUPON_DESCRIPTION'] = (!empty($coupon_description) ? '<strong>' . $coupon_description . '</strong>' : '');
            }
        }

        if (!empty($summary_lines)) {
            $email_text .= "\n\n" . EMAIL_SUMMARY_HEADER . "\n" . implode("\n", $summary_lines) . "\n" . EMAIL_SEPARATOR;
            $html_msg['EMAIL_SUMMARY_HEADER'] = EMAIL_SUMMARY_HEADER;
            $html_msg['EMAIL_SUMMARY_LINES'] = nl2br(implode("\n", $summary_lines));
        } else {
            $html_msg['EMAIL_SUMMARY_HEADER'] = '';
            $html_msg['EMAIL_SUMMARY_LINES'] = '';
        }

        if (NEW_SIGNUP_GIFT_VOUCHER_AMOUNT > 0) {
            // -----
            // Not zen_create_coupon_code() (core's own deprecated-since-v2.0.0 wrapper) or a
            // bare create_coupon_code() (never existed under that name at all - this call would
            // have fataled with "Call to undefined function" on every signup, on every version,
            // the moment a store had this core setting turned on). Coupon::generateRandomCouponCode()
            // is core's own current replacement, @since exactly the v2.0.0 floor this plugin
            // targets.
            //
            $gv_coupon_code = Coupon::generateRandomCouponCode();
            $db->Execute(
                "INSERT INTO " . TABLE_COUPONS . "
                    (coupon_code, coupon_type, coupon_amount, date_created)
                 VALUES
                    ('" . $gv_coupon_code . "', 'G', '" . NEW_SIGNUP_GIFT_VOUCHER_AMOUNT . "', now())"
            );
            $insert_id = $db->Insert_ID();
            $db->Execute(
                "INSERT INTO " . TABLE_COUPON_EMAIL_TRACK . "
                    (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent)
                 VALUES
                    ($insert_id , '0', 'Admin', '" . $customers_email_address . "', now())"
            );

            // if on, add in GV explanation
            $email_text .= "\n\n" . sprintf(EMAIL_GV_INCENTIVE_HEADER, $currencies->format(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT)) .
            sprintf(EMAIL_GV_REDEEM, $gv_coupon_code) .
                EMAIL_GV_LINK .
                zen_catalog_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $gv_coupon_code, 'NONSSL', false) . "\n\n" .
                EMAIL_GV_LINK_OTHER .
                EMAIL_SEPARATOR;
            $html_msg['GV_WORTH'] = str_replace('\n', '', sprintf(EMAIL_GV_INCENTIVE_HEADER, $currencies->format(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT)) );
            $html_msg['GV_REDEEM'] = str_replace('\n', '', str_replace('\n\n', '<br>', sprintf(EMAIL_GV_REDEEM, '<strong>' . $gv_coupon_code . '</strong>')));
            $html_msg['GV_CODE_NUM'] = $gv_coupon_code;
            $html_msg['GV_CODE_URL'] = str_replace('\n', '', EMAIL_GV_LINK . '<a href="' . zen_catalog_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $gv_coupon_code, 'NONSSL', false) . '">' . TEXT_GV_NAME . ': ' . $gv_coupon_code . '</a>');
            $html_msg['GV_LINK_OTHER'] = EMAIL_GV_LINK_OTHER;
        } else {
            $html_msg['GV_WORTH'] = '';
            $html_msg['GV_REDEEM'] = '';
            $html_msg['GV_CODE_NUM'] = '';
            $html_msg['GV_CODE_URL'] = '';
            $html_msg['GV_LINK_OTHER'] = '';
        }

        // -----
        // The activation link - this is the only way the customer's account moves out of
        // AUTH_NO_PURCHASE and becomes usable, so it's always included.
        //
        $activation_link = zen_catalog_href_link(FILENAME_ADMIN_ADD_USER_ACTIVATE, 'token=' . $token, 'SSL');
        $email_text .= "\n\n" . EMAIL_ACTIVATION_HEADER . "\n" . $activation_link . "\n" . EMAIL_SEPARATOR;
        $html_msg['EMAIL_ACTIVATION_HEADER'] = EMAIL_ACTIVATION_HEADER;
        $html_msg['EMAIL_ACTIVATION_LINK'] = '<a href="' . $activation_link . '">' . $activation_link . '</a>';

        // -----
        // Add in regular email welcome text.
        //
        $email_text .= "\n\n" . EMAIL_TEXT_1 . (($customers_password !== false) ? sprintf(EMAIL_TEXT_2, $customers_password) : '') . EMAIL_TEXT_3 . EMAIL_CONTACT . EMAIL_GV_CLOSURE;

        $html_msg['EMAIL_MESSAGE_HTML'] = str_replace('\n', '', EMAIL_TEXT_1 . (($customers_password !== false) ? sprintf(EMAIL_TEXT_2, $customers_password) : '') . EMAIL_TEXT_3);
        $html_msg['EMAIL_CONTACT_OWNER'] = str_replace('\n', '', EMAIL_CONTACT);
        $html_msg['EMAIL_CLOSURE'] = nl2br(EMAIL_GV_CLOSURE);

        // include create-account-specific disclaimer
        $email_text .= "\n\n" . sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, STORE_OWNER_EMAIL_ADDRESS). "\n\n";
        $html_msg['EMAIL_DISCLAIMER'] = sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, '<a href="mailto:' . STORE_OWNER_EMAIL_ADDRESS . '">'. STORE_OWNER_EMAIL_ADDRESS .' </a>');

        // send welcome email
        zen_mail($name, $customers_email_address, EMAIL_SUBJECT, $email_text, STORE_NAME, EMAIL_FROM, $html_msg, 'welcome');

        // send additional emails
        if (SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO_STATUS === '1' && SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO !== '') {
            $extra_info = email_collect_extra_info($name, $customers_email_address, $customers_firstname . ' ' . $customers_lastname , $customers_email_address);
            $admin_html_msg['EXTRA_INFO'] = $extra_info['HTML'];
            if ($customers_password !== false) {
                $email_text = str_replace($customers_password, 'xxxx', $email_text);
                $html_msg['EMAIL_MESSAGE_HTML'] = str_replace($customers_password, 'xxxx', $html_msg['EMAIL_MESSAGE_HTML']);
            }
            zen_mail('', SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO, '[ACCOUNT CREATED BY ADMINISTRATOR]' . ' ' . EMAIL_SUBJECT, $email_text . $extra_info['TEXT'], STORE_NAME, EMAIL_FROM, $html_msg, 'welcome_extra');
        }
    }

    public function validateCustomer($info)
    {
        global $db;

        $errors = [];
        $cInfo = [];

        $customers_firstname = (isset($info['customers_firstname'])) ? zen_db_prepare_input($info['customers_firstname']) : '';
        $customers_lastname = (isset($info['customers_lastname'])) ? zen_db_prepare_input($info['customers_lastname']) : '';
        $customers_email_address = (isset($info['customers_email_address'])) ? zen_db_prepare_input($info['customers_email_address']) : '';
        $customers_telephone = (isset($info['customers_telephone'])) ? zen_db_prepare_input($info['customers_telephone']) : '';
        $customers_group_pricing = (isset($info['customers_group_pricing'])) ? (int)$info['customers_group_pricing'] : 0;

        if (strlen($customers_firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
            $errors[] = ERROR_FIRST_NAME . " ($customers_firstname)";
        }

        if (strlen($customers_lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
            $errors[] = ERROR_LAST_NAME . " ($customers_lastname)";
        }

        if (strlen($customers_email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
            $errors[] = ERROR_EMAIL_LENGTH . " ($customers_email_address)";
        } elseif (!zen_validate_email($customers_email_address)) {
            $errors[] = ERROR_EMAIL_INVALID . " ($customers_email_address)";
        } else {
            $check_email = $db->Execute(
                "SELECT customers_id
                   FROM " . TABLE_CUSTOMERS . "
                  WHERE customers_email_address = '" . zen_db_input($customers_email_address) . "'
                  LIMIT 1"
            );
            if (!$check_email->EOF) {
                $errors[] = sprintf(ERROR_EMAIL_ADDRESS_ERROR_EXISTS, $customers_email_address);
            }
        }

        // -----
        // Phone is optional at this step - the customer can supply or correct it themselves
        // when they complete activation (see catalog/admin_add_user_activate.php), and may
        // simply never give one at all. If a value *was* given here, it's still format-checked
        // so an obvious typo doesn't go through silently.
        //
        if ($customers_telephone !== '' && ($errMsg = $this->validatePhone($customers_telephone)) !== false) {
            $errors[] = $errMsg;
        }

        if ($customers_group_pricing !== 0) {
            $cgp = $db->Execute(
                "SELECT group_name
                   FROM " . TABLE_GROUP_PRICING . "
                  WHERE group_id = " . $customers_group_pricing . "
                  LIMIT 1"
            );
            if ($cgp->EOF) {
                $errors[] = sprintf(ERROR_UNKNOWN_GROUP_PRICING, $customers_group_pricing);
            }
        }

        if (count($errors)) {
            $cInfo = new objectInfo($info);
        }

        // -----
        // Return a simple array suitable for retrieval via list.
        //
        return [$cInfo, $errors];
    }

    // -----
    // Validate the phone number supplied.
    //
    protected function validatePhone($telephone)
    {
        // -----
        // Bypass the world phone prefix if it's the first character in the phone number.
        //
        $start_pos = 0;
        if (ENTRY_PHONE_NO_DELIM_WORLD !== false && strpos($telephone, ENTRY_PHONE_NO_DELIM_WORLD) === 0) {
            $start_pos = 1;
        }

        // -----
        // Remove all the delimiter characters, the remaining telephone should contain only digits (0-9).
        //
        $telephone = str_replace(str_split(ENTRY_PHONE_NO_DELIMS), '', $telephone);

        for ($i = $start_pos, $errorMessage = false, $num_digits = 0, $telephone_len = strlen($telephone); $i < $telephone_len && !$errorMessage; $i++) {
            if ($telephone[$i] < '0' || $telephone[$i] > '9') {
                $errorMessage = sprintf(ENTRY_PHONE_NO_CHAR_ERROR, $telephone[$i]);
            } else {
                $num_digits++;
            }
        }

        if ($errorMessage !== false) {
            if ($num_digits < ENTRY_PHONE_NO_MIN_DIGITS) {
                $errorMessage = ENTRY_PHONE_NO_MIN_ERROR;
            } elseif ($num_digits > ENTRY_PHONE_NO_MAX_DIGITS) {
                $errorMessage = ENTRY_PHONE_NO_MAX_ERROR;
            }
        }
        return $errorMessage;
    }

    // -----
    // Feeds the admin's "Resend the Welcome E-Mail" dropdown. Now keyed on whether the
    // customer has completed activation (customers_authorization != AUTH_OK), rather than the
    // old "has never logged in" proxy for the same idea.
    //
    public function createCustomerDropdown()
    {
        global $db;
        $customers = [
            ['id' => '0', 'text' => TEXT_PLEASE_CHOOSE],
        ];

        $and_clause = (defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID')) ? (' AND c.customers_id != ' . CHECKOUT_ONE_GUEST_CUSTOMER_ID) : '';
        $customersRecords = $db->Execute(
            "SELECT c.customers_id, c.customers_firstname, c.customers_lastname, c.customers_email_address
               FROM " . TABLE_CUSTOMERS . " c
              WHERE c.customers_authorization != " . self::AUTH_OK . "
              $and_clause
              ORDER BY c.customers_firstname, c.customers_lastname, c.customers_email_address"
        );
        foreach ($customersRecords as $next_customer) {
            $customers[] = [
                'id' => $next_customer['customers_id'],
                'text' => $next_customer['customers_firstname'] . ' ' . $next_customer['customers_lastname'] . ' (' . $next_customer['customers_email_address'] . ')'
            ];
        }
        return $customers;
    }

    // -----
    // Permanently deletes customers who signed up before $before_date (compared against their
    // registration date) and never went on to enter a complete address anywhere - i.e. they
    // never activated at all, or activated but never logged in to finish their profile. "No
    // complete address" (blank street AND blank postcode on every address_book row they have)
    // is deliberately the whole test: Zen Cart core's own checkout already refuses to let any
    // customer place an order without one, so nobody who ever placed an order can match this,
    // with no separate orders check needed. Returns the number of customers deleted, or false
    // if $before_date isn't a well-formed date.
    //
    public function deleteAbandonedSignups($before_date)
    {
        global $db;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$before_date)) {
            return false;
        }

        $candidates = $db->Execute(
            "SELECT c.customers_id
               FROM " . TABLE_CUSTOMERS . " c
                    INNER JOIN " . TABLE_CUSTOMERS_INFO . " ci
                        ON ci.customers_info_id = c.customers_id
              WHERE ci.customers_info_date_account_created < '" . zen_db_input($before_date) . "'
                AND NOT EXISTS (
                    SELECT 1
                      FROM " . TABLE_ADDRESS_BOOK . " ab
                     WHERE ab.customers_id = c.customers_id
                       AND ab.entry_street_address != ''
                       AND ab.entry_postcode != ''
                )"
        );

        $deleted = 0;
        foreach ($candidates as $candidate) {
            $customers_id = (int)$candidate['customers_id'];

            $db->Execute("DELETE FROM " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " WHERE customers_id = " . $customers_id);
            $db->Execute("DELETE FROM " . TABLE_CUSTOMERS_BASKET . " WHERE customers_id = " . $customers_id);
            $db->Execute("DELETE FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id = " . $customers_id);
            $db->Execute("DELETE FROM " . TABLE_ADMIN_ADD_USER_TOKENS . " WHERE customers_id = " . $customers_id);
            $db->Execute("DELETE FROM " . TABLE_CUSTOMERS_INFO . " WHERE customers_info_id = " . $customers_id);
            $db->Execute("DELETE FROM " . TABLE_CUSTOMERS . " WHERE customers_id = " . $customers_id);

            $deleted++;
        }

        return $deleted;
    }
}
