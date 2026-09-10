<?php
/**
 * @package Admin Add Customer
 * @subpackage plugins
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://zen-cart.com GNU Public License V2.0
 * @version $Id: filenames.php 2026-07-26 05:30:22Z dbltoe $
 */
// -----
// NOTE: This file is loaded on BOTH the admin side (for the add_customers.php admin tool)
// and the catalog/storefront side (for the admin_add_user_activate.php landing page), so it
// must not die() just because IS_ADMIN_FLAG (an admin-only constant) isn't defined.
//

if (!defined('FILENAME_ADD_CUSTOMERS')) {
    define('FILENAME_ADD_CUSTOMERS', 'add_customers');
}

if (!defined('FILENAME_ADMIN_ADD_USER_ACTIVATE')) {
    define('FILENAME_ADMIN_ADD_USER_ACTIVATE', 'admin_add_user_activate');
}

// -----
// This plugin's own activation-token table. Defined as a TABLE_* constant (rather than used
// as a raw string) so it stays correct on stores that use a database table prefix, matching
// how core (and other encapsulated plugins) reference their own tables.
//
if (!defined('TABLE_ADMIN_ADD_USER_TOKENS')) {
    define('TABLE_ADMIN_ADD_USER_TOKENS', (defined('DB_PREFIX') ? DB_PREFIX : '') . 'admin_add_user_tokens');
}

// -----
// The admin menu builder resolves this page's box title (BOX_CUSTOMERS_ADD_CUSTOMERS) on every
// admin page, not just when add_customers.php itself is loaded. Defining it here, alongside
// FILENAME_ADD_CUSTOMERS, guarantees both are available together wherever this file loads,
// rather than depending on admin/includes/languages/english/extra_definitions/ being merged
// globally for encapsulated plugins. This box title is only meaningful in the admin, so it's
// only defined there.
//
if (defined('IS_ADMIN_FLAG') && !defined('BOX_CUSTOMERS_ADD_CUSTOMERS')) {
    define('BOX_CUSTOMERS_ADD_CUSTOMERS', 'Add Customer(s)');
}
