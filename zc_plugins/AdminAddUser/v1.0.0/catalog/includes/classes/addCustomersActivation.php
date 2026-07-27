<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://zen-cart.com GNU Public License V2.0
 * @version $Id: addCustomersActivation.php 2026-07-26 05:30:22Z dbltoe $
 */
// -----
// Catalog-side counterpart to admin/includes/classes/addCustomers.php. Admin and catalog are
// separate execution contexts in Zen Cart - each has its own includes/classes/ tree, and
// there's no supported way to load one side's classes from the other - so this duplicates
// just the handful of methods the activation landing page needs, rather than depending on the
// admin-side class.
//
// This class used to also validate/apply a completion form (gender, DOB, company, address,
// etc.). That form and its catalog template were dropped: activation now happens immediately
// on a valid token click, and the customer is sent to log in and use Zen Cart's own account/
// address-book pages for everything else - those already handle every one of those fields,
// with zero plugin code or catalog-template involvement.
//
class addCustomersActivation extends base
{
    // -----
    // Mirrors addCustomers::AUTH_OK on the admin side.
    //
    protected const AUTH_OK = 0;

    // -----
    // Looks up a pending customer + their assigned coupon by the token from the emailed
    // activation link. $max_age_hours bounds how old an unused token can be before it's
    // treated as expired (independent of core's own, v2.2.0-only, activation-token system).
    //
    public function getPendingCustomerByToken($token, $max_age_hours = 24)
    {
        global $db;

        $result = $db->Execute(
            "SELECT t.customers_id, t.coupon_id, t.created_at, c.customers_group_pricing, c.customers_whole
               FROM " . TABLE_ADMIN_ADD_USER_TOKENS . " t
                    INNER JOIN " . TABLE_CUSTOMERS . " c
                        ON c.customers_id = t.customers_id
              WHERE t.token = '" . zen_db_input($token) . "'
                AND t.created_at >= DATE_SUB(now(), INTERVAL " . (int)$max_age_hours . " HOUR)
              LIMIT 1"
        );
        return (!$result->EOF) ? $result->fields : false;
    }

    public function getAssignedCoupon($coupon_id)
    {
        global $db;

        $coupon_id = (int)$coupon_id;
        if ($coupon_id === 0) {
            return false;
        }
        $coupon = $db->Execute(
            "SELECT c.coupon_code, c.uses_per_coupon, c.uses_per_user, cd.coupon_description
               FROM " . TABLE_COUPONS . " c
                    LEFT JOIN " . TABLE_COUPONS_DESCRIPTION . " cd
                        ON cd.coupon_id = c.coupon_id
                       AND cd.language_id = " . (int)$_SESSION['languages_id'] . "
              WHERE c.coupon_id = " . $coupon_id . "
              LIMIT 1"
        );
        return (!$coupon->EOF) ? $coupon->fields : false;
    }

    public function getGroupName($group_id)
    {
        global $db;

        $group_id = (int)$group_id;
        if ($group_id === 0) {
            return '';
        }
        $group = $db->Execute(
            "SELECT group_name, group_percentage
               FROM " . TABLE_GROUP_PRICING . "
              WHERE group_id = " . $group_id . "
              LIMIT 1"
        );
        if ($group->EOF) {
            return '';
        }
        return sprintf(TEXT_GROUP_DISCOUNT_LABEL, $group->fields['group_name'], (int)$group->fields['group_percentage']);
    }

    // -----
    // Validates and consumes the token from the activation link: flips the customer to fully
    // authorized and deletes the token row. Returns false if the token doesn't match an
    // outstanding request (already used, or never existed for this customer).
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
}
