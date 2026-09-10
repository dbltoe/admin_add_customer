<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://zen-cart.com GNU Public License V2.0
 * @version $Id: header_php.php 2026-07-26 05:30:22Z dbltoe $
 */
// -----
// Catalog-side landing page for the activation link emailed by
// admin/includes/classes/addCustomers.php::sendWelcomeEmail(). This plugin uses its own
// activation-token table rather than core's built-in one (only present since v2.2.0) - see
// the readme for why.
//
// This page never renders its own content - it validates the token, activates the account if
// valid, and redirects straight to core's own login page with a session-flash summary
// message. The customer then logs in (with the password emailed to them) and uses Zen Cart's
// own account/address-book pages for anything else - both already handle every field this
// plugin doesn't collect up front (address, gender, DOB, company, etc.) with zero plugin code
// or catalog-template involvement. That also means this page needs no content template of its
// own, sidestepping the whole class of "core's catalog loader isn't zc_plugins-aware" problems
// a custom template ran into during live testing.
//
// -----
// Unlike admin pages (whose DIR_WS_CLASSES gets rebased to the dispatching plugin's own
// admin/ tree by the admin router), Zen Cart's catalog-side page loader does not rebase
// DIR_WS_CLASSES for a plugin's custom page - it always points at core's own
// catalog/includes/classes/, regardless of which plugin's page is executing. Confirmed via a
// real "Failed opening required" fatal on a live site. So this class is loaded by an
// __DIR__-relative path instead, which is correct regardless of any include_path/DIR_WS_*
// behavior.
//
require __DIR__ . '/../../../classes/addCustomersActivation.php';

// -----
// Same underlying issue as the require above: Zen Cart's catalog-side page-specific language
// loader (lang.<pagename>.php, array-return format) isn't plugin-aware for a plugin's own
// catalog page either - confirmed via a real "Undefined constant" fatal on a live site. So
// this file is loaded directly and its returned array applied to define() here, rather than
// relying on it being auto-discovered.
//
$acfa_lang_file = __DIR__ . '/../../../languages/' . $_SESSION['language'] . '/lang.admin_add_user_activate.php';
if (!is_file($acfa_lang_file)) {
    $acfa_lang_file = __DIR__ . '/../../../languages/english/lang.admin_add_user_activate.php';
}
$acfa_lang_define = require $acfa_lang_file;
foreach ($acfa_lang_define as $acfa_lang_key => $acfa_lang_value) {
    if (!defined($acfa_lang_key)) {
        define($acfa_lang_key, $acfa_lang_value);
    }
}
unset($acfa_lang_file, $acfa_lang_define, $acfa_lang_key, $acfa_lang_value);

$acfa_activation = new addCustomersActivation();

$acfa_token = (isset($_GET['token'])) ? trim((string)$_GET['token']) : '';
$acfa_customer = ($acfa_token !== '') ? $acfa_activation->getPendingCustomerByToken($acfa_token) : false;

if ($acfa_customer === false) {
    $messageStack->add_session(TEXT_INVALID_TOKEN, 'error');
    zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
}

$acfa_customers_id = (int)$acfa_customer['customers_id'];

// -----
// The summary (group/wholesale/coupon) is built from the token-lookup row before the token
// itself is deleted by completeActivation() below.
//
$acfa_summary_lines = [];

$acfa_group_name = $acfa_activation->getGroupName($acfa_customer['customers_group_pricing'] ?? 0);
if ($acfa_group_name !== '') {
    $acfa_summary_lines[] = sprintf(TEXT_STATUS_GROUP, $acfa_group_name);
}

if ((int)$acfa_customer['customers_whole'] > 0) {
    $acfa_summary_lines[] = TEXT_STATUS_WHOLESALE;
}

$acfa_coupon = $acfa_activation->getAssignedCoupon($acfa_customer['coupon_id'] ?? 0);
if ($acfa_coupon !== false) {
    $acfa_coupon_usage = ((int)$acfa_coupon['uses_per_user'] === 1) ? TEXT_STATUS_COUPON_ONE_TIME : TEXT_STATUS_COUPON_CONTINUING;
    $acfa_coupon_description = (!empty($acfa_coupon['coupon_description'])) ? ' - ' . $acfa_coupon['coupon_description'] : '';
    $acfa_summary_lines[] = sprintf(TEXT_STATUS_COUPON, $acfa_coupon['coupon_code']) . ' ' . $acfa_coupon_usage . $acfa_coupon_description;
}

// -----
// The return value here matters: completeActivation() can fail (token already consumed by a
// prior request - e.g. an email client/security scanner pre-fetching the link before the
// customer actually clicks it) even though getPendingCustomerByToken() found a match moments
// earlier. Previously this wasn't checked at all, so a failed activation still showed the
// success message and sent the customer on to log in - since AUTH_NO_PURCHASE already permits
// login and browsing, they could log in and edit their address with no error ever shown,
// while customers_authorization silently never actually flipped to approved.
//
if ($acfa_activation->completeActivation($acfa_customers_id, $acfa_token) === false) {
    $messageStack->add_session(TEXT_INVALID_TOKEN, 'error');
    zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
}

$acfa_message = TEXT_ACTIVATION_SUCCESS;
if (!empty($acfa_summary_lines)) {
    $acfa_message .= ' ' . TEXT_STATUS_INTRO . ' ' . implode(' ', $acfa_summary_lines);
}
$messageStack->add_session($acfa_message, 'success');

zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
