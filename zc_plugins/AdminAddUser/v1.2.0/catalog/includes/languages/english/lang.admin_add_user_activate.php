<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://zen-cart.com GNU Public License V2.0
 * @version $Id: lang.admin_add_user_activate.php 2026-07-26 05:30:22Z dbltoe $
 */
$define = [
    'TEXT_INVALID_TOKEN' => 'This activation link is invalid, has expired, or has already been used. If you still need to activate your account, please contact us so we can send a new link.',

    'TEXT_ACTIVATION_SUCCESS' => 'Your account is now active! Please log in using the password that was emailed to you.',

    'TEXT_STATUS_INTRO' => 'Here is a summary of what has been set up for your account:',
    'TEXT_STATUS_GROUP' => 'Pricing Group: %s.',

    // -----
    // Used by addCustomersActivation::getGroupName() to build a plain-language label from the
    // group's raw name/percentage - the customer sees this, not the group's internal name
    // alone, e.g. "Group 10 - 20% Off Retail".
    //
    'TEXT_GROUP_DISCOUNT_LABEL' => '%s - %d%% Off Retail',
    // -----
    // Deliberately doesn't show the raw wholesale level number - that's an internal pricing
    // detail with no customer-facing meaning, and showing it invited "how do I get to level
    // #?" questions. This line only appears at all when the level is > 0, so a fixed
    // "Activated" is all that's needed.
    //
    'TEXT_STATUS_WHOLESALE' => 'Wholesale Level: Activated.',
    'TEXT_STATUS_COUPON' => 'Coupon assigned: %s',
    'TEXT_STATUS_COUPON_ONE_TIME' => '(one-time use).',
    'TEXT_STATUS_COUPON_CONTINUING' => '(usable more than once).',
];

return $define;
